<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_course_transfer', get_string('pluginname', 'local_course_transfer'));
    $ADMIN->add('localplugins', $settings);

    $title = get_string('page_log','local_course_transfer');
    $desc  = get_string('page_log_help','local_course_transfer');
    $settings->add(new admin_setting_heading('local_course_transfer_logs_header', $title, $desc));

    //Setting - Folder full path where to put backups
    $title = get_string('host', 'local_course_transfer');
    $desc = get_string('host_help', 'local_course_transfer');
    $settings->add(new admin_setting_heading('local_course_transfer_host_header', $title, $desc));
    $title = get_string('backup_path', 'local_course_transfer');
    $desc = get_string('backup_path_help', 'local_course_transfer');
    $settings->add(new admin_setting_configdirectory('local_course_transfer_backup_path', $title, $desc, ''));
    //Setting - Default time for the backup
    $title = get_string('backup_default_time', 'local_course_transfer');
    $desc = get_string('backup_default_time_help', 'local_course_transfer');
    $settings->add(new admin_setting_configtime('local_course_transfer_backup_default_time_h', 'local_course_transfer_backup_default_time_m',
                    $title, $desc, array('h' => 1, 'm' => 0)));

    //Setting - Folder full path where to get backups for restore
    $title = get_string('remote', 'local_course_transfer');
    $desc = get_string('remote_help', 'local_course_transfer');
    $settings->add(new admin_setting_heading('local_course_transfer_remote_header', $title, $desc));
    $title = get_string('restore_path', 'local_course_transfer');
    $desc = get_string('restore_path_help', 'local_course_transfer');
    $settings->add(new admin_setting_configdirectory('local_course_transfer_restore_path', $title, $desc, ''));


    //Setting - Restore server Name Settings
    $title = get_string('restore_server_name', 'local_course_transfer');
    $desc = get_string('restore_server_name_help', 'local_course_transfer');
    $settings->add(new admin_setting_configtext('local_course_transfer_restore_server_name', $title, $desc, ''));
    //Setting - Restore server Settings
    $title = get_string('restore_server', 'local_course_transfer');
    $desc = get_string('restore_server_help', 'local_course_transfer');
    $settings->add(new admin_setting_configtext('local_course_transfer_restore_server', $title, $desc, ''));
    //Setting - Restore HTTP(S)
    $title = get_string('restore_https', 'local_course_transfer');
    $desc = get_string('restore_https_help', 'local_course_transfer');
    $settings->add(new admin_setting_configcheckbox('local_course_transfer_restore_https', $title, $desc, 1));
    //Setting - Restore Token
    $title = get_string('restore_token', 'local_course_transfer');
    $desc = get_string('restore_token_help', 'local_course_transfer');
    $settings->add(new admin_setting_configtext('local_course_transfer_restore_token', $title, $desc, 'changeme'));
}


