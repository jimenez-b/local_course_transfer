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
global $CFG;

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


    //Context System
    $context = context_system::instance();

    require_login(1, false);

    //Check the capability
    if (!has_capability('local/course_transfer:do_transfer', $context))
    {
        redirect('/', get_string('error_do_transfer_not_allowed', 'local_course_transfer'), 10);
        return;
    }
    
    $backupid = optional_param('id', 0, PARAM_INT);
    $backup = $DB->get_record('course_transfer_backup', array('id' => $backupid));
    
    $PAGE->set_url("$CFG->wwwroot/local/course_transfer/delete_transfer.php");
    $message = '';
    if($backup == false)
    {
        $message = $OUTPUT->notification(get_string('error_delete_backup_not_exist', 'local_course_transfer'));
    }
    else if($backup->completed != null && $backup->completed != 0)
    {
        $message = $OUTPUT->notification(get_string('error_delete_backup_started', 'local_course_transfer'));
    }
    else
    {
        $DB->delete_records('course_transfer_backup', array('id' => $backup->id));
        $message = get_string('success_delete_transfer', 'local_course_transfer');
    }
    redirect(new moodle_url('/local/course_transfer/index.php'), $message, 10);
    
    return;
}

display_page();

