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
defined('MOODLE_INTERNAL') || die;

/**
 * Run this function each time an update is available 
 * @global stdClass $CFG
 * @global moodle_database $DB
 * @param int $oldversion
 * @return boolean
 */
function xmldb_local_course_transfer_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    $updatesuccess = true;

    if ($oldversion < 2020081400) {

        // Define field users_unenrolled to be added to course_transfer_backup.
        $table = new xmldb_table('course_transfer_backup');
        $field = new xmldb_field('users_unenroled', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'restore_restoreid');

        // Conditionally launch add field users_unenroled.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field unenroll_users to be added to course_transfer_backup.
        $table = new xmldb_table('course_transfer_backup');
        $field = new xmldb_field('unenrol_users', XMLDB_TYPE_INTEGER, '1', null, null, null, '1', 'users_unenroled');

        // Conditionally launch add field unenrol_users.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Course_transfer savepoint reached.
        upgrade_plugin_savepoint(true, 2020081400, 'local', 'course_transfer');
    }


    return $updatesuccess;
}
