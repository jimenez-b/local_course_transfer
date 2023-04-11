<?php

/**
 * *************************************************************************
 * *                  Course Transfer                               **
 * *************************************************************************
 * @package     local                                                     **
 * @subpackage  course_transfer                                     **
 * @name        Course Transfer                                     **
 * @copyright   York University - UIT                                                 **
 * @link        https://uit.yorku.ca                                          **
 * @author      Patrick Thibaudeau                                            **
 * @license     All rights reserved                                       **
 * *************************************************************************
 * ************************************************************************ */

namespace local_course_transfer\task;

include_once($CFG->dirroot . '/local/course_transfer/lib.php');

class course_transfer extends \core\task\scheduled_task {

    public function get_name() {
        // Shown in admin screens
        return get_string('task_course_transfer', 'local_course_transfer');
    }

    /**
     * Execute the cron for the gradebook send
     * @global \stdClass $CFG
     * @global \moodle_database $DB
     */
    public function execute() {
        global $CFG, $DB;

        mtrace('Course Transfer process starts...');

        $courses_to_backup = $DB->get_records_select('course_transfer_backup', 'timescheduled <= ? AND timestarted IS NULL', array(time()));

        $nbcoursesbackedup = 0;
        foreach ($courses_to_backup as $course_info) {
            mtrace("Create backup for course {$course_info->courseid}");

            //Execute the course import
            $return = $this->backup_course($course_info);

            if ($return == 1) {
                $nbcoursesbackedup++;
            }
        }

        $courses_to_restore = $DB->get_records_select('course_transfer_restor', 'timestarted IS NULL');
        $nbcoursesrestored = 0;
        foreach ($courses_to_restore as $course_info) {
            mtrace("Restore backup file named {$course_info->backupfile}");

            //Execute the course import
            $return = $this->restore_course($course_info);

            if ($return == 1) {
                $nbcoursesrestored++;
//                We only allow one course restoration per process
//                break;
            }
        }

        mtrace("END - Course Transfer - Course backed up=$nbcoursesbackedup - - Course restored=$nbcoursesrestored");

        //Unenrol Users from courses
        $courses_to_unenrol = $DB->get_records_select('course_transfer_backup', 'unenrol_users = 1 AND users_unenroled = 0 AND restore_restoreid IS NOT NULL ');

        foreach ($courses_to_unenrol as $ctu) {
            mtrace("Hiding course process started for course id: $ctu->courseid");
            $restore_params = array();
            $restore_params['restoreid'] = $ctu->restore_restoreid;
            $restorestatus = local_course_transfer_execute_ws_call('course_transfer_get_restore_status', $restore_params);
            $response = json_decode($restorestatus->response);
            if ($response->completed == 'Completed') {
                $this->unenrol_users($ctu->id, $ctu->courseid);
            } else {
                mtrace("Hiding course process not completed for course id: $ctu->courseid");
            }
        }
    }

    /**
     * Import a course content from a template to the new course
     * @global \stdClass $CFG
     * @global \stdClass $USER
     * @global \moodle_database $DB
     * @param \stdClass $course_info The data in table course_transfer_backup
     */
    private function backup_course($course_info) {
        GLOBAL $CFG, $USER, $DB;

        //Requires
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/moodle2/backup_plan_builder.class.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        require_once($CFG->dirroot . '/backup/util/ui/import_extensions.php');

        require_once($CFG->dirroot . '/local/course_transfer/lib.php');

        mtrace("Start Backup for course ID={$course_info->courseid}...");
        $course_info->timestarted = time();
        $DB->update_record('course_transfer_backup', $course_info);

        $course_info->completed = 0;
        $course_info->log = '';

        /* @var $bc \backup_controller */
        $bc = null;
        /* @var $backupfile \stored_file */
        $backupfile = null;

        try {
            //Get the course
            $course_to_backup = get_course($course_info->courseid);

            mtrace(" - Course ID={$course_info->courseid} - Course name={$course_to_backup->shortname}...");


            mtrace(" - Creating Backup controller");
            //Create a backup from the template course in mode import
            $bc = new \backup_controller(\backup::TYPE_1COURSE, $course_to_backup->id, \backup::FORMAT_MOODLE, \backup::INTERACTIVE_NO, \backup::MODE_GENERAL, $course_info->userid);

            //Backup filename
            $rand = time() . '' . rand(100000, 999999);
            $courseid = $course_to_backup->id;
            $courseshortname = $course_to_backup->shortname;
            $backup_filename = clean_filename("backup-$courseid-$courseshortname-$rand.mbz");
            mtrace(" - Backup filename will be $backup_filename");

            //Now we get the settings the user choose from the course info
            $settings = json_decode($course_info->settings);
            //Add the filename to the settings
            $settings->filename = $backup_filename;

            //Set the settings for the backup
            $bc = $this->backup_set_settings($bc, $settings);

            mtrace(" - Execute Backup");
            //Execute the backup
            $bc->execute_plan();
            //The result contains the backup file object
            $result = $bc->get_results();
            /* @var $backupfile \stored_file */
            $backupfile = $result['backup_destination'];
            mtrace(" - Copying backup to transfer folder");
            //Copy the file in the backup folder
            $backupfile->copy_content_to("$CFG->local_course_transfer_backup_path/$backup_filename");
            //And delete the backup object
            $backupfile->delete();
            $bc->destroy();
            $bc = null;
            mtrace(" - Backup Controller deleted");

            $course_info->completed = 1;

            //Now check the backup is here
            if (!is_readable("$CFG->local_course_transfer_backup_path/$backup_filename")) {
                $message = "Backup file {$CFG->local_course_transfer_backup_path}/$backup_filename does not exist or is not readable";
                $exception = new \Exception($message, 1008001);
                throw $exception;
            }

            //Add the course name to the restore settings
            $course_info->restore_settings = json_decode($course_info->restore_settings);
            $course_info->restore_settings->course_fullname = $course_to_backup->fullname;
            $course_info->restore_settings->course_shortname = $course_to_backup->shortname;
            $course_info->restore_settings = json_encode($course_info->restore_settings);

            //Send the request to the Restore server to start the restoration of this backup
            $restore_params = array();
            $restore_params['categoryid'] = $course_info->restore_categoryid;
            $restore_params['settings'] = $course_info->restore_settings;
            $restore_params['backupid'] = $course_info->id;
            $restore_params['backupfile'] = $backup_filename;

            $restore_result = local_course_transfer_execute_ws_call('course_transfer_restore_request', $restore_params);


            //Error with the call
            if ($restore_result->code != '200 OK' || $restore_result->return_object === null || (int) $restore_result->return_object < 0) {
                mtrace(" - Error during push to restore server");
                $course_info->log = '<pre>' . var_export($restore_result, true) . '</pre>';
                $course_info->completed = -1;
            } else {
                //Success update
                $course_info->completed = 1;
                $course_info->timecompleted = time();
                $course_info->restore_restoreid = (int) $restore_result->return_object;
            }
        } catch (\Exception $e) {
            mtrace("Exception triggered - Course backup ID {$course_info->courseid} - " . $e->getMessage());
            //Exception if course does not exist!
            if ($e instanceof \dml_missing_record_exception && $e->tablename == 'course') {
                $course_info->completed = -1;
                $course_info->log .= "Course with ID {$course_info->courseid} does not exist - Transfer stopped\r\n";
                mtrace("Course does not exist");
            }
            //Error Backup file is not readable
            elseif ($e instanceof \Exception && $e->code == 1008001) {
                $course_info->completed = -1;
                $message = $e->getMessage();
                $course_info->log .= "$message\r\n";
                mtrace($message);
            }
            //Default error
            else {
                $course_info->timecompleted = time();
                $course_info->completed = -1;
                $course_info->log .= var_export($e, true);
                mtrace("Unknown error, check the log for more information - " . $e->getMessage());
            }

            //Delete the backup file and the controller IF they are set
            if (isset($bc) && $bc instanceof \backup_controller) {
                $bc->destroy();
                $bc = null;
            }
            if (isset($backupfile) && $backupfile instanceof \stored_file) {
                $backupfile->delete();
            }
        }

        //Update it one last time with the result of the course_info
        $DB->update_record('course_transfer_backup', $course_info);

        return $course_info->completed;
    }

    /**
     * Restore a course backup creating a new course.
     * @global \stdClass $CFG
     * @global \stdClass $USER
     * @global \moodle_database $DB
     * @param \stdClass $course_info The data in table course_transfer_backup
     */
    private function restore_course($course_info) {
        GLOBAL $CFG, $USER, $DB;

        //Requires
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/moodle2/backup_plan_builder.class.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        require_once($CFG->dirroot . '/backup/util/ui/import_extensions.php');
        require_once($CFG->dirroot . '/lib/moodlelib.php');
        require_once($CFG->dirroot . '/lib/accesslib.php');

        require_once($CFG->dirroot . '/local/course_transfer/lib.php');


        mtrace("Start Restore for course backup name={$course_info->backupfile}...");
        try {
            $course_info->timestarted = time();
            $DB->update_record('course_transfer_restor', $course_info);

            $course_info->completed = 0;
            $course_info->log = '';

            /* @var $rc \restore_controller */
            $rc = null;
            $temp_backup_path = '';

            //Now we get the settings the user choose from the course info
            $settings = json_decode($course_info->settings);

            $backup_filepath = '';

            mtrace(" - Execute Restore");
            //We add some debug so we can know where is stopped if it stopped without exception
            $course_info->log .= " - Course restore started - " . time() . "\r\n";
            $DB->update_record('course_transfer_restor', $course_info);

            //Get the variables
            $categoryid = $course_info->categoryid;
            $backup_filepath = "$CFG->local_course_transfer_restore_path/$course_info->backupfile";
            //At the end it's just temporary name
            $course_fullname = $settings->course_fullname . '_RESTORE_IN_PROGRESS_' . time();
            $course_shortname = $settings->course_shortname . '_RESTORE_IN_PROGRESS_' . time();

            //Check category
            $category = $DB->get_record('course_categories', array('id' => $categoryid), '*', MUST_EXIST);
            mtrace(" - Restore in category {$category->name}");

            //We add some debug so we can know where is stopped if it stopped without exception
            $course_info->log .= " - Before Check Backup file - " . time() . "\r\n";
            $DB->update_record('course_transfer_restor', $course_info);

            //Check if backup file exists and is readable
            //Now check the backup is here
            if (!is_readable($backup_filepath)) {
                $message = "Backup file $backup_filepath does not exist or is not readable, restore not possible";
                $exception = new \Exception($message, 1008002);
                throw $exception;
            }

            mtrace(" - Create temp folder");
            //We add some debug so we can know where is stopped if it stopped without exception
            $course_info->log .= " - Before Create Temp folder - " . time() . "\r\n";
            $DB->update_record('course_transfer_restor', $course_info);
            // Now try to restore the same backup
            $backupid = 'random' . rand(100000, 999999) . time();
            $temp_backup_path = "{$CFG->tempdir}/backup/$backupid";
            check_dir_exists($temp_backup_path);
            // Extract backup file.
            mtrace(" - Extract backup in temp folder");
            //We add some debug so we can know where is stopped if it stopped without exception
            $course_info->log .= " - Before Extract backup - " . time() . "\r\n";
            $DB->update_record('course_transfer_restor', $course_info);
            get_file_packer('application/vnd.moodle.backup')->extract_to_pathname($backup_filepath, $temp_backup_path);


            //If anonymized, mark the users as deleted so they will not be present on the server
            if (isset($settings->anonymize) && $settings->anonymize == 1) {
                mtrace(" - While anonymizing users mark them as deleted so they will not show at all");
                //We add some debug so we can know where is stopped if it stopped without exception
                $course_info->log .= " - Before Anonymizing - " . time() . "\r\n";
                $DB->update_record('course_transfer_restor', $course_info);
                $users = file_get_contents("$temp_backup_path/users.xml");
                $users = str_replace('<deleted>0', '<deleted>1', $users);
                file_put_contents("$temp_backup_path/users.xml", $users);
            }

            mtrace(" - Create a new course for the restore");
            //We add some debug so we can know where is stopped if it stopped without exception
            $course_info->log .= " - Before Create Course - " . time() . "\r\n";
            $DB->update_record('course_transfer_restor', $course_info);
            //We create the course now
            $course_info->courseid = \restore_dbops::create_new_course($course_fullname, $course_shortname, $categoryid);
            //Load the course
            $course = get_course($course_info->courseid);

            mtrace(" - New course created - Course ID={$course_info->courseid}");

            mtrace(" - Create restore controller");
            //We add some debug so we can know where is stopped if it stopped without exception
            $course_info->log .= " - Before Create Restore Controller - " . time() . "\r\n";
            $DB->update_record('course_transfer_restor', $course_info);
            $rc = new \restore_controller($backupid, $course_info->courseid, \backup::INTERACTIVE_NO, \backup::MODE_GENERAL, $USER->id, \backup::TARGET_NEW_COURSE);

            //Execute the restore

            mtrace(" - Check if need conversion");
            // Check if the format conversion must happen first.
            if ($rc->get_status() == \backup::STATUS_REQUIRE_CONV) {
                mtrace(" - Need conversion!");
                //We add some debug so we can know where is stopped if it stopped without exception
                $course_info->log .= " - Before Execute Convert - " . time() . "\r\n";
                $DB->update_record('course_transfer_restor', $course_info);
                $rc->convert();
            }

            mtrace(" - Execute precheck");
            //We add some debug so we can know where is stopped if it stopped without exception
            $course_info->log .= " - Before Execute Precheck - " . time() . "\r\n";
            $DB->update_record('course_transfer_restor', $course_info);
            if (!$rc->execute_precheck()) {
                $precheck_results = $rc->get_precheck_results();
                //We add some debug so we can know where is stopped if it stopped without exception
                $course_info->log .= " - Execute Precheck FAILED - " . time() . "\r\n";
                $DB->update_record('course_transfer_restor', $course_info);
                $message = "Error on Execute PreCheck Restore, restore not possible. results: " . json_encode($precheck_results);
                $exception = new \Exception($message, 1008003);
                throw $exception;
            }
            mtrace(" - Execute restore");
            //We add some debug so we can know where is stopped if it stopped without exception
            $course_info->log .= " - Before Execute Restore - " . time() . "\r\n";
            $DB->update_record('course_transfer_restor', $course_info);
            $rc->execute_plan();
            //Destroy the restore controller
            mtrace(" - Restore successful - deletion of the restore controller");
            //We add some debug so we can know where is stopped if it stopped without exception
            $course_info->log .= " - Before Destroy Restore object - " . time() . "\r\n";
            $DB->update_record('course_transfer_restor', $course_info);
            $rc->destroy();
            unset($rc);
            $rc = null;
            //Delete the temp folder
            fulldelete($temp_backup_path);

            if (isset($settings->anonymize) && $settings->anonymize == 1 || isset($settings->users) && $settings->users == 0) {
                //Do a reset on the course just in case
                mtrace(" - Start course data reset");
                //We add some debug so we can know where is stopped if it stopped without exception
                $course_info->log .= " - Before Course data reset - " . time() . "\r\n";
                $DB->update_record('course_transfer_restor', $course_info);
                $data_reset = new \stdClass();
                $data_reset->id = $course_info->courseid;
                //Settings
                $data_reset->reset_notes = true;
                $data_reset->reset_comments = true;
                $data_reset->reset_completion = true;
                $data_reset->delete_blog_associations = true;
                //Roles
                $data_reset->reset_roles_overrides = true;
                $data_reset->reset_roles_local = true;
                $data_reset->unenrol_users = array();
                $roles = get_assignable_roles(\context_course::instance($course_info->courseid));
                $roles[0] = get_string('noroles', 'role');
                foreach ($roles as $role_id => $role_name) {
                    $data_reset->unenrol_users[] = $role_id;
                }
                //Gradebook
                $data_reset->reset_gradebook_items = false; // We want to keep the gradebook items!!!
                $data_reset->reset_groups_remove = true;
                $data_reset->reset_groups_members = true;
                $data_reset->reset_groupings_remove = true;
                $data_reset->reset_groupings_members = true;
                //Mods
                if ($allmods = $DB->get_records('modules')) {
                    //Loop on all module looking for data
                    foreach ($allmods as $mod) {
                        $modname = $mod->name;
                        //Exception for glossary and forums
                        if ($modname == 'glossary' || $modname == 'forum') {
                            continue;
                        }

                        $modfile = $CFG->dirroot . "/mod/$modname/lib.php";
                        $mod_reset_course_form_defaults = $modname . '_reset_course_form_defaults';
                        if (file_exists($modfile)) {
                            @include_once($modfile);
                            if (function_exists($mod_reset_course_form_defaults)) {
                                if ($moddefs = $mod_reset_course_form_defaults($course)) {
                                    //If the module have default value for the reset, add them and force true on everything
                                    foreach ($moddefs as $mod_param => $value) {
                                        $data_reset->$mod_param = 1;
                                    }
                                }
                            }
                        }
                    }
                }
                //And reset the course
                $status = reset_course_userdata($data_reset);
                mtrace(' - Course ' . $course->id . ' - Course resetted');
                //We add some debug so we can know where is stopped if it stopped without exception
                $course_info->log .= " - After course data reset - " . time() . "\r\n";
                $DB->update_record('course_transfer_restor', $course_info);
            }

            //Update the restore info
            $course_info->completed = 1;
            $course_info->timecompleted = time();
        } catch (Exception $e) {
            mtrace("Exception triggered - Restore ID {$course_info->id} - " . $e->getMessage());
            //Exception if course does not exist!
            if ($e instanceof \dml_missing_record_exception && $e->tablename == 'category') {
                $course_info->completed = -1;
                $course_info->log .= "Category with ID {$course_info->categoryid} does not exist - Restore stopped\r\n";
                mtrace("Category does not exist");
            }
            //Error Backup file is not readable
            elseif ($e instanceof \Exception && ($e->code == 1008002 || $e->code == 1008003)) {
                $course_info->completed = -1;
                $message = $e->getMessage();
                $course_info->log .= "$message\r\n";
                mtrace($message);
            }
            //Default error
            else {
                $course_info->timecompleted = time();
                $course_info->completed = -1;
                $course_info->log .= var_export($e, true);
                mtrace("Unknown error, check the log for more information - " . $e->getMessage());
            }

            //Delete the backup file and the controller IF they are set
            if (isset($rc) && $rc instanceof \restore_controller) {
                $rc->destroy();
                $rc = null;
            }
            if (isset($temp_backup_path) && $temp_backup_path != '' && is_dir($temp_backup_path)) {
                fulldelete($temp_backup_path);
            }
        }

        $DB->update_record('course_transfer_restor', $course_info);

        //Delete the backup file now if completed
        if ($course_info->completed == 1 && is_file($backup_filepath) && is_writable($backup_filepath)) {
            mtrace(" - Delete backup file " . $backup_filepath);
            unlink($backup_filepath);
        }

        return $course_info->completed;
    }

    /**
     * This function save the settings for the backup. It returns the updated Backup controller
     * @param \backup_controller $bc
     * @param \stdClass $data
     * @return \backup_controller The updated backup controller
     */
    private function backup_set_settings($bc, $data) {
        mtrace(" - Update backup settings...");
        //We have to update the tasks in order to get change the settings
        /* @var $plan \backup_plan */
        $plan = $bc->get_plan();
        $tasks = $plan->get_tasks();
        /* @var $task \backup_task */
        foreach ($tasks as &$task) {
            // We are only interesting in the backup root task for this stage
            if ($task instanceof \backup_root_task) {
                // Get all settings into a var so we can iterate by reference
                $settings = $task->get_settings();
                /* @var $setting \base_setting */
                foreach ($settings as &$setting) {
                    $name = $setting->get_ui_name();
                    $settingname = str_replace('setting_root_', '', $name);
                    if (isset($data->$settingname)) {
                        $setting->set_value($data->$settingname);
                    }
                }
            }
        }

        //Save the controller after changing the settings otherwise the settings are not correctly saved in the backup
        $backupid = $bc->get_backupid();
        mtrace(" - Saving backup settings...");
        $bc->save_controller();
        mtrace(" - Loading backup controller with settings...");
        //And then reload the controller
        $bc = $bc->load_controller($backupid);

        return $bc;
    }

    /**
     * Force hidden course on users
     * @global \stdClass $CFG
     * @global \moodle_database $DB
     * @param int $id course_transfer_backup id
     * @param int $courseid
     * @return boolean
     */
    private function unenrol_users($id, $courseid) {
        global $CFG, $DB;
        include_once($CFG->diroot . '/lib/accesslib.php');
        $context = \context_course::instance($courseid);
        $course_users = enrol_get_course_users($courseid);
        $student_role = $DB->get_record('role', ['shortname' => 'student']);
        $course_transfer_override_role = $DB->get_record('role', ['shortname' => 'coursetransferoverride']);

        foreach ($course_users as $user) {
            if (!user_has_role_assignment($user->id, $student_role->id, $context->id)) {
                role_assign($course_transfer_override_role->id, $user->id, $context->id);
            }
        }

        //Hide course
        $DB->update_record('course', ['id' => $courseid, 'visible' => 0, 'timemodified' => time()]);
//        $course_element = new \core_course_list_element($course);
//        $course_manager = new \core_course\management\helper();
//        $course_manager->action_course_hide($course_element);

        // Update record
        $params = [
            'id' => $id,
            'users_unenroled' => 1
        ];

        $DB->update_record('course_transfer_backup', $params);

        mtrace("Hiding course process completed for course id: $courseid");

        return true;
    }

}
