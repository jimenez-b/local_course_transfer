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
require_once($CFG->libdir . "/externallib.php");

class local_course_transfer_external extends external_api
{
    /* ----------------------- Restore request method ----------------------- */

    /**
     * Send a Restore request - Returns description of method parameters
     * @return external_function_parameters
     */
    public static function restore_request_parameters()
    {
        $categoryid = new external_value(PARAM_INT, 'ID of the category to restore the course in', VALUE_REQUIRED);
        $settings = new external_value(PARAM_TEXT, 'JSON String of the restore options to use', VALUE_REQUIRED);
        $backupid = new external_value(PARAM_INT, 'ID of the backup in the Backup server', VALUE_REQUIRED);
        $backupfile = new external_value(PARAM_TEXT, 'File name of the backup to restore', VALUE_REQUIRED);

        return new external_function_parameters(array(
            'categoryid' => $categoryid,
            'settings' => $settings,
            'backupid' => $backupid,
            'backupfile' => $backupfile
        ));
    }

    /**
     * Send a Restore request - Send the request to the restore server to start restoring a course from a backup file
     * @global stdClass $CFG
     * @global moodle_database $DB
     * @return string welcome message
     */
    public static function restore_request($categoryid, $settings, $backupid, $backupfile)
    {
        global $CFG, $DB;

        //Parameter validation
        //REQUIRED
        $params = self::validate_parameters(self::restore_request_parameters(), array(
                    'categoryid' => $categoryid,
                    'settings' => $settings,
                    'backupid' => $backupid,
                    'backupfile' => $backupfile
        ));

        //Prepare the object
        $restore_object = new \stdClass();

        $restore_object->courseid = null;
        $restore_object->categoryid = $params['categoryid'];
        $restore_object->settings = $params['settings'];
        $restore_object->backupfile = $params['backupfile'];
        $restore_object->timecreated = time();
        $restore_object->completed = 0;
        $restore_object->log = '';
        $restore_object->timestarted = null;
        $restore_object->timestarted = null;
        $restore_object->backup_backupid = $params['backupid'];

        $category = $DB->get_record('course_categories', array('id' => $params['categoryid']));

        if ($category == false)
        {
            return -1;
        }

        $restoreid = $DB->insert_record('course_transfer_restor', $restore_object);

        if ($restoreid == false)
        {
            return -99;
        }
        else
        {
            return $restoreid;
        }
    }

    /**
     * Restore a course request - Returns description
     * @return external_description
     */
    public static function restore_request_returns()
    {
        $description = 'Return Code: >0=Success ID if the restore, -1=Error category does not exist, -99=Unknown Error';
        return new external_value(PARAM_INT, $description);
    }

    /* ----------------------- End Restore Request ------------------- */

    /* ----------------------- Get Backup Status ----------------------- */

    /**
     * Get a backup object status - Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_backup_status_parameters()
    {
        $backupid = new external_value(PARAM_INT, 'ID of the backup object', VALUE_REQUIRED);

        return new external_function_parameters(array(
            'backupid' => $backupid
        ));
    }

    /**
     * get the backup object - Return a JSON object with the backup info
     * @global stdClass $CFG
     * @global moodle_database $DB
     * @return string welcome message
     */
    public static function get_backup_status($backupid)
    {
        global $CFG, $DB;
        require_once("$CFG->dirroot/local/course_transfer/classes/datatable/base.php");

        //Parameter validation
        //REQUIRED
        $params = self::validate_parameters(self::get_backup_status_parameters(), array(
                    'backupid' => $backupid
        ));

        //Prepare the object
        $backup = $DB->get_record('course_transfer_backup', array('id' => $params['backupid']));
        
        $course = $DB->get_record('course', array('id' => $backup->courseid));

        $user  = $DB->get_record('user', array('id' => $backup->userid));

        $return_object = new \stdClass();

        $return_object->completed = get_string('select_completed_' . ((int) $backup->completed), 'local_course_transfer');
        
        $return_object->coursename = $course->fullname;
        $linktitle = get_string('open_course', 'local_course_transfer');
        $return_object->coursename .= " <a href=\"$CFG->wwwroot/course/view.php?id=$course->id\" title=\"$linktitle\" target=\"_blank\"><i class=\"fa fa-link\"></i></a>";
        
        $return_object->username = fullname($user);

        if ($backup->timecreated > 0)
        {
            $return_object->timecreated = \date(local_course_transfer\datatable\base::$date_format, $backup->timecreated);
        }
        else
        {
            $return_object->timecreated = ' - ';
        }
        if ($backup->timestarted > 0)
        {
            $return_object->timestarted = \date(local_course_transfer\datatable\base::$date_format, $backup->timestarted);
        }
        else
        {
            $return_object->timestarted = ' - ';
        }
        if ($backup->timecompleted > 0)
        {
            $return_object->timecompleted = \date(local_course_transfer\datatable\base::$date_format, $backup->timecompleted);
        }
        else
        {
            $return_object->timecompleted = ' - ';
        }
        
        //Add the log
        if ($backup->log != '')
        {
            $return_object->log = '<pre>' . $backup->log . '</pre>';
        }


        return $return_object;
    }

    /**
     * Get a backup object status - Returns description
     * @return external_description
     */
    public static function get_backup_status_returns()
    {
        $fields = array(
            'completed' => new external_value(PARAM_TEXT, 'Completed status', VALUE_OPTIONAL),
            'coursename' => new external_value(PARAM_RAW, 'Course name', VALUE_OPTIONAL),
            'username' => new external_value(PARAM_RAW, 'User name', VALUE_OPTIONAL),
            'timecreated' => new external_value(PARAM_TEXT, 'Created time', VALUE_OPTIONAL),
            'timestarted' => new external_value(PARAM_TEXT, 'Process Start time', VALUE_OPTIONAL),
            'timecompleted' => new external_value(PARAM_TEXT, 'Process Completion time', VALUE_OPTIONAL),
            'log' => new external_value(PARAM_TEXT, 'Log if an error occured', VALUE_OPTIONAL)
        );
        $description = 'Return JSON string of the backup object';
        return new external_single_structure($fields, $description);
        //return new external_value(PARAM_, $description);
    }

    /* ----------------------- End Get Backup status ------------------- */
    
    /* ----------------------- Get Restore Status ----------------------- */

    /**
     * Get a restore object status - Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_restore_status_parameters()
    {
        $restoreid = new external_value(PARAM_INT, 'ID of the restore object', VALUE_REQUIRED);

        return new external_function_parameters(array(
            'restoreid' => $restoreid
        ));
    }

    /**
     * get the restore object - Return a JSON object with the restore info
     * @global stdClass $CFG
     * @global moodle_database $DB
     * @return string welcome message
     */
    public static function get_restore_status($restoreid)
    {
        global $CFG, $DB;
        require_once("$CFG->dirroot/local/course_transfer/classes/datatable/base.php");

        //Parameter validation
        //REQUIRED
        $params = self::validate_parameters(self::get_restore_status_parameters(), array(
                    'restoreid' => $restoreid
        ));

        //Prepare the object
        $restore = $DB->get_record('course_transfer_restor', array('id' => $params['restoreid']));

        $category = $DB->get_record('course_categories', array('id' => $restore->categoryid));
        $course = $DB->get_record('course', array('id' => $restore->courseid));


        $return_object = new \stdClass();

        $return_object->completed = get_string('select_completed_' . ((int) $restore->completed), 'local_course_transfer');
        if ($category != false)
        {
            $return_object->categoryname = $category->name;
        }
        if ($course != false)
        {
            $return_object->coursename = $course->fullname;
            $linktitle = get_string('open_course', 'local_course_transfer');
            $return_object->coursename .= " <a href=\"$CFG->wwwroot/course/view.php?id=$course->id\" title=\"$linktitle\" target=\"_blank\"><i class=\"fa fa-link\"></i></a>";
        }

        if ($restore->timecreated > 0)
        {
            $return_object->timecreated = \date(local_course_transfer\datatable\base::$date_format, $restore->timecreated);
        }
        else
        {
            $return_object->timecreated = ' - ';
        }
        if ($restore->timestarted > 0)
        {
            $return_object->timestarted = \date(local_course_transfer\datatable\base::$date_format, $restore->timestarted);
        }
        else
        {
            $return_object->timestarted = ' - ';
        }
        if ($restore->timecompleted > 0)
        {
            $return_object->timecompleted = \date(local_course_transfer\datatable\base::$date_format, $restore->timecompleted);
        }
        else
        {
            $return_object->timecompleted = ' - ';
        }
        
        //Add the log
        if ($restore->log != '')
        {
            $return_object->log = '<pre>' . $restore->log . '</pre>';
        }


        return $return_object;
    }

    /**
     * Get a restore object status - Returns description
     * @return external_description
     */
    public static function get_restore_status_returns()
    {
        $fields = array(
            'completed' => new external_value(PARAM_TEXT, 'Completed status', VALUE_OPTIONAL),
            'categoryname' => new external_value(PARAM_TEXT, 'Category name', VALUE_OPTIONAL),
            'coursename' => new external_value(PARAM_RAW, 'Course name', VALUE_OPTIONAL),
            'timecreated' => new external_value(PARAM_TEXT, 'Created time', VALUE_OPTIONAL),
            'timestarted' => new external_value(PARAM_TEXT, 'Process Start time', VALUE_OPTIONAL),
            'timecompleted' => new external_value(PARAM_TEXT, 'Process Completion time', VALUE_OPTIONAL),
            'log' => new external_value(PARAM_RAW, 'Log if an error occured', VALUE_OPTIONAL)
        );
        $description = 'Return JSON string of the restore object';
        return new external_single_structure($fields, $description);
    }

    /* ----------------------- End Get restore status ------------------- */
    
    /* ----------------------- Get Categories --------------------------- */

    /**
     * Get category list - Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_categories_parameters()
    {

        return new external_function_parameters(array());
    }

    /**
     * Get the category list - Return a JSON array of the categories
     * @global stdClass $CFG
     * @global moodle_database $DB
     * @return string welcome message
     */
    public static function get_categories()
    {
        global $CFG, $DB;
        //CONU - added check to include proper file according to version
        if ($CFG->version > 3.10) {
            require_once("$CFG->dirroot/course/classes/category.php");
        } else {
            require_once("$CFG->dirroot/lib/coursecatlib.php");
        }
        
        //Parameter validation
        //REQUIRED
        $params = self::validate_parameters(self::get_categories_parameters(), array());

        //Prepare the object
        //CONU - added check to include proper file according to version
        if ($CFG->version > 3.10) {
            $categories = core_course_category::make_categories_list();
        } else {
            $categories = coursecat::make_categories_list();
        }
        
        $return_object = array();
        foreach($categories as $id => $name)
        {
            $category = new \stdClass();
            $category->id = $id;
            $category->name = $name;
            $return_object[] = $category;
        }

        return $return_object;
    }

    /**
     * Get the category list - Returns description
     * @return external_description
     */
    public static function get_categories_returns()
    {
        $fields = array(
            'id' => new external_value(PARAM_INT, 'Category ID', VALUE_REQUIRED),
            'name' => new external_value(PARAM_TEXT, 'Category name', VALUE_REQUIRED)
        );
        $description = 'Return JSON string of the categories';
        $descriptionsingle = 'Category ID and name';
        return new external_multiple_structure(new external_single_structure($fields, $descriptionsingle), $description);
    }

    /* ----------------------- End Get Category list ------------------- */
}
