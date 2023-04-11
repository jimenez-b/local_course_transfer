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
function display_page() {
    // CHECK And PREPARE DATA
    global $DB, $PAGE, $OUTPUT, $CFG, $USER;
    
    $backups_to_delete = $DB->get_records('course_transfer_backup', ['completed' => 1]);
    $ids = [];
    $i = 0;
    foreach ($backups_to_delete as $btd) {
        if($btd->unenrol_users && $btd->users_unenroled) {
            $ids[$i] = $btd->id;
        }
        
        if (!$btd->unenrol_users) {
            $restore_params = array();
            $restore_params['restoreid'] = $btd->restore_restoreid;
            $restorestatus = local_course_transfer_execute_ws_call('course_transfer_get_restore_status', $restore_params);
            $response = json_decode($restorestatus->response);
            if ($response->completed == 'Completed') {
                $ids[$i] = $btd->id;
            }
        }
        $i++;
    }
    
    foreach ($ids as $key => $id) {
        $DB->delete_records('course_transfer_backup',['id' => $id]);
    }
}

display_page();
