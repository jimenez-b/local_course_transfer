<?php

require_once('../../config.php');
require_once('lib.php');

/**
 * Display the content of the page
 * @global stdClass $CFG
 * @global moodle_database $DB
 * @global core_renderer $OUTPUT
 * @global moodle_page $PAGE
 * @global stdobject $SESSION
 * @global stdobject $USER
 */
function display_page()
{
    global $CFG, $USER;

    //Requires
    require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
    require_once($CFG->dirroot . '/backup/moodle2/backup_plan_builder.class.php');
    require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
    require_once($CFG->dirroot . '/backup/util/dbops/backup_controller_dbops.class.php');
    require_once($CFG->dirroot . '/backup/util/ui/import_extensions.php');

    /*$test = new stdClass();
    
    $test->users = 1;
    $test->anonymize = 1;
    
    echo json_encode($test);die;*/

    //Use this page as backup/restore test
    return;

    $task = \core\task\manager::get_default_scheduled_task('local_course_transfer\task\course_transfer');
    $task->execute();die;

    try
    {
        print_object(json_decode('0') === null);
        die;
        $template_course = get_course(273);
        
        
        //Create a backup from the template course in mode import
        $bc = new \backup_controller(\backup::TYPE_1COURSE, $template_course->id, \backup::FORMAT_MOODLE, \backup::INTERACTIVE_NO, \backup::MODE_GENERAL, $USER->id);

        /*$settings = array(
            // Config name                      => Setting name
            'backup_general_users' => 'users',
            'backup_general_anonymize' => 'anonymize',
            'backup_general_role_assignments' => 'role_assignments',
            'backup_general_activities' => 'activities',
            'backup_general_blocks' => 'blocks',
            'backup_general_filters' => 'filters',
            'backup_general_comments' => 'comments',
            'backup_general_badges' => 'badges',
            'backup_general_userscompletion' => 'userscompletion',
            'backup_general_logs' => 'logs',
            'backup_general_histories' => 'grade_histories',
            'backup_general_questionbank' => 'questionbank'
        );*/
        $data = new \stdClass();
        $data->setting_root_users = 1;
        $data->setting_root_anonymize = 1;
        $data->setting_root_filename = 'backup_test_' . rand(100000, 999999) . '.mbz';
        
        //We have to update the tasks in order to get change the settings
        /* @var $plan backup_plan */
        $plan = $bc->get_plan();
        $tasks = $plan->get_tasks();
        $changes = 0;
        foreach ($tasks as &$task)
        {
            // We are only interesting in the backup root task for this stage
            if ($task instanceof \backup_root_task)
            {
                // Get all settings into a var so we can iterate by reference
                $settings = $task->get_settings();
                foreach ($settings as &$setting)
                {
                    $name = $setting->get_ui_name();
                    if (isset($data->$name) )
                    {
                        $setting->set_value($data->$name);
                        $changes++;
                    }
                    /*else if (!isset($data->$name) && $setting->get_ui_type() == \backup_setting::UI_HTML_CHECKBOX && $setting->get_value())
                    {
                        $setting->set_value(0);
                        $changes++;
                    }*/
                }
            }
        }
        //Save the controller after changing the settings otherwise the settings are not correctly saved in the backup
        $backupid = $bc->get_backupid();
        $bc->save_controller();
        //And then reload the controller
        $bc = $bc->load_controller($backupid);
        
        /*
        echo $changes;
        echo $bc->get_backupid();
        
        $plan = $bc->get_plan();
        $tasks = $plan->get_tasks();
        $task = $tasks[0];
        $setting = $task->get_setting('filename');
        echo $setting->get_value();*/
        
        //backup_controller_dbops::save_controller($bc, $bc->calculate_checksum());
        //die;
        
        //print_object(\backup_controller_dbops::get_moodle_backup_information($bc->get_backupid()));
/*
        $plan = $bc->get_plan();
        $tasks = $plan->get_tasks();
        $task = $tasks[0];
        $setting = $task->get_setting('filename');
        echo $setting->get_value();*/

        //Set the name of the backup
        /* @var $setting base_setting */
        //$setting = $plan->get_setting('filename');
        //$setting->set_value('backup_test_' . rand(100000, 999999) . '.mbz');

/*
        //Here set the default settings + custom settings if exist
        foreach ($settings as $config => $settingname)
        {
            $value = get_config('backup', $config);
            if (isset($values[$settingname]))
            {
                $value = $values[$settingname];
            }
            if ($value === false)
            {
                // Ignore this because the config has not been set. get_config
                // returns false if a setting doesn't exist, '0' is returned when
                // the configuration is set to false.
                $bc->log('Could not find a value for the config ' . $config, BACKUP::LOG_DEBUG);
                continue;
            }
            $locked = (get_config('backup', $config . '_locked') == true);
            if ($plan->setting_exists($settingname))
            {
                $setting = $plan->get_setting($settingname);
                if ($setting->get_value() != $value || 1 == 1)
                {
                    $setting->set_value($value);
                    if ($locked)
                    {
                        $setting->set_status(base_setting::LOCKED_BY_CONFIG);
                    }
                }
            }
            else
            {
                $bc->log('Unknown setting: ' . $setting, BACKUP::LOG_DEBUG);
            }
        }*/

        //$tasks = $plan->get_tasks();
        /* @var $task backup_task */
        /* $task = $tasks[0];
          $setting = $task->get_setting('anonymize');
          $value = $setting->get_value();
          echo "$value<br/>"; */


        //Execute the backup in default mode
        $bc->execute_plan();
        $result = $bc->get_results();
        /* @var $result stored_file */
        $backupfile = $result['backup_destination'];
        $backupfile->copy_content_to('D:\\backup_test.mbz');
        $backupfile->delete();

        $bc->destroy();

        $bc = null;
        
        $categoryid = 46;
        $coursefullname = $template_course->fullname;
        $courseshortname = $template_course->shortname;
        $mbzpath = 'D:\\backup_test.mbz';

        //Now try to restore the same backup
        // Extract backup file.
        $backupid = 'random' . rand(100000, 999999) . time();
        $backuppath = $CFG->tempdir . '/backup/' . $backupid;
        check_dir_exists($backuppath);
        get_file_packer('application/vnd.moodle.backup')->extract_to_pathname($mbzpath, $backuppath);
        
        
        
        //If anonymized, mark the users as deleted so they will not be present on the server
        $users = file_get_contents("$backuppath/users.xml");
        $users = str_replace('<deleted>0', '<deleted>1', $users);
        file_put_contents("$backuppath/users.xml", $users);


        $newcourseid = restore_dbops::create_new_course($coursefullname, $courseshortname, $categoryid);
        $rc = new restore_controller($backupid, $newcourseid, backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id, backup::TARGET_NEW_COURSE);
        
        
        try
        {
            $rc->execute_precheck();
            $rc->execute_plan();
            $rc->destroy();
        }
        catch (Exception $e)
        {
            print_object($e);
        }

        echo "New course ID: $newcourseid";

        // Must set restore_controller variable to null so that php
        // garbage-collects it; otherwise the file will be left open and
        // attempts to delete it will cause a permission error on Windows
        // systems, breaking unit tests.
        $rc = null;



        die;
        //Get the backup ID
        $backupid = $bc->get_backupid();
        mtrace('Course ' . $target_course->id . ' - Create backup for the content from the template');

        //Create a restoration process with the target course
        $rc = new \restore_controller($backupid, $target_course->id, \backup::INTERACTIVE_NO, \backup::MODE_IMPORT, $USER->id, \backup::TARGET_CURRENT_DELETING);
        //Delete all the previous content in this course
        \restore_dbops::delete_course_content($target_course->id);


        //Execute a precheck that will prepare the data
        $rc->execute_precheck();
        //Execute the restore
        $rc->execute_plan();
        mtrace('Course ' . $target_course->id . ' - Restore template content in new course');

        //Destroy the restoration and backup data
        $rc->destroy();
        $bc->destroy();

        mtrace('Course ' . $target_course->id . ' - Content restored successfuly');
    }
    catch (\Exception $e)
    {
        //Exception if course does not exist!
        if($e instanceof \dml_missing_record_exception && $e->tablename == 'course')
        {
            echo 'Course does not exist';
        }
        print_object($e);
        mtrace('ERROR during Course ' . $target_course->id . 'content restoration: ' . $e->getMessage());
    }
}

display_page();
