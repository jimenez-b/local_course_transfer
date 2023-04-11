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
 * ************************************************************************ */
require_once(dirname(__FILE__) . '/../../config.php');

require_once("$CFG->dirroot/local/course_transfer/classes/datatable/course.php");
require_once("$CFG->dirroot/local/course_transfer/classes/datatable/backup_log.php");
require_once("$CFG->dirroot/local/course_transfer/classes/datatable/restore_log.php");

/**
 * Display the content of the page
 * @global stdobject $CFG
 * @global moodle_database $DB
 * @global core_renderer $OUTPUT
 * @global moodle_page $PAGE
 * @global stdobject $SESSION
 * @global stdobject $USER
 */
function display_page()
{
    // CHECK And PREPARE DATA
    global $DB, $PAGE, $OUTPUT, $CFG, $USER;

    //Get the action
    $action = optional_param('action', '', PARAM_TEXT);

    if ($action == '')
    {
        echo 0;
        return;
    }

    //Context System
    $context = context_system::instance();

    require_login(1, false);

    //Check the capability
    if (!has_capability('local/course_transfer:log_view', $context))
    {
        echo get_string('error_log_not_allowed', 'local_course_transfer');
        return;
    }

    //List the actions
    if ($action == 'backup_log')
    {
        $draw = optional_param('draw', 1, PARAM_INT);
        $length = optional_param('length', 10, PARAM_INT);
        $start = optional_param('start', 0, PARAM_INT);
        //Moodle optional param_array does not work for that because it contains a sub array
        $order = $_REQUEST['order'];
        $columns = $_REQUEST['columns'];

        $search = \local_course_transfer\datatable\backup_log::prepare_search_from_datatable($columns);

        $order_text = $columns[$order[0]['column']]['data'] . ' ' . $order[0]['dir'];


        $request = new \local_course_transfer\datatable\backup_log($draw, $length, $start, $order_text, $search);

        //Print the json
        echo $request->return_json();
    }
    else if ($action == 'restore_log')
    {
        $draw = optional_param('draw', 1, PARAM_INT);
        $length = optional_param('length', 10, PARAM_INT);
        $start = optional_param('start', 0, PARAM_INT);
        //Moodle optional param_array does not work for that because it contains a sub array
        $order = $_REQUEST['order'];
        $columns = $_REQUEST['columns'];

        $search = \local_course_transfer\datatable\restore_log::prepare_search_from_datatable($columns);

        $order_text = $columns[$order[0]['column']]['data'] . ' ' . $order[0]['dir'];


        $request = new \local_course_transfer\datatable\restore_log($draw, $length, $start, $order_text, $search);

        //Print the json
        echo $request->return_json();
    }
    else if ($action == 'courseselection')
    {
        $draw = optional_param('draw', 1, PARAM_INT);
        $length = optional_param('length', 10, PARAM_INT);
        $start = optional_param('start', 0, PARAM_INT);
        //Moodle optional param_array does not work for that because it contains a sub array
        $order = $_REQUEST['order'];
        $columns = $_REQUEST['columns'];

        $search = \local_course_transfer\datatable\course::prepare_search_from_datatable($columns);

        $order_text = $columns[$order[0]['column']]['data'] . ' ' . $order[0]['dir'];


        $request = new \local_course_transfer\datatable\course($draw, $length, $start, $order_text, $search);

        //Print the json
        echo $request->return_json();
    }
    else if ($action == 'backup_log_moreinfo')
    {
        $id = optional_param('id', 0, PARAM_INT);
        
        echo \local_course_transfer\datatable\backup_log::format_data_display($id);
    }
    else if ($action == 'restore_log_moreinfo')
    {
        $id = optional_param('id', 0, PARAM_INT);
        
        echo \local_course_transfer\datatable\restore_log::format_data_display($id);
    }
}

/**
 * Sort from older to newer so smaller to bigger
 * @param int $a Timestamp 1
 * @param int $b Timestamp 2
 * @return int
 */
function overdue_activities_sort($a, $b)
{
    if ($a->timesubmitted == $b->timesubmitted)
    {
        return 0;
    }
    return ($a->timesubmitted < $b->timesubmitted) ? -1 : 1;
}

display_page();
