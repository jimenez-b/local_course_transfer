<?php

/**
 * *************************************************************************
 * *                        Course Transfer                               **
 * *************************************************************************
 * @package     local                                                     **
 * @subpackage  course_transfer                                           **
 * @name        Course Transfer                                           **
 * @copyright   York University - UIT                                     **
 * @link        https://uit.yorku.ca                                      **
 * @author      Patrick Thibaudeau                                        **
 * @license     All rights reserved                                       **
 * ************************************************************************ */

$string['pluginname'] = 'Course Transfer';


$string['add_new_transfer_process'] = 'Add a new course transfer';
$string['add_transfer_submit'] = 'Add transfer to the queue';
$string['backup_activities'] = 'Include activities and resources';
$string['backup_activities_help'] = 'Check to include activities and resources';
$string['backup_anonymize'] = 'Anonymize user information';
$string['backup_anonymize_help'] = 'Check to anonymize user info so it will not appear in the restore server. User content will still be added like Glossary entries and Forum topics';
$string['backup_badges'] = 'Include badges';
$string['backup_badges_help'] = 'Check to include badges';
$string['backup_blocks'] = 'Include blocks';
$string['backup_blocks_help'] = 'Check to include blocks';
$string['backup_comments'] = 'Check to include comments';
$string['backup_comments_help'] = 'Check to include comments';
$string['backup_default_time'] = 'Backup default time';
$string['backup_default_time_help'] = 'Default time when the cron job will be executed. The restore will be trigger right after the backup is completed.';
$string['backup_grade_histories'] = 'Include grade history';
$string['backup_grade_histories_help'] = 'Check to include grade history for the students';
$string['backup_info'] = 'Backup Info';
$string['backup_logs'] = 'Include course logs';
$string['backup_logs_help'] = 'Check to include course logs';
$string['backup_questionbank'] = 'Include Question bank';
$string['backup_questionbank_help'] = 'Check to include question bank';
$string['backup_role_assignments'] = 'Include user role assignments';
$string['backup_role_assignments_help'] = 'Check to include user role assignments in the course';
$string['backup_users'] = 'Include enrolled users';
$string['backup_users_help'] = 'Check to include users inrolled in the course';
$string['backup_userscompletion'] = 'Include user completion detail';
$string['backup_userscompletion_help'] = 'Check to include user completion detail';
$string['backup_path'] = 'Backup folder path';
$string['backup_path_help'] = 'Full path of the backup folder. Can be inside or outside moodledata folder as long as the Apache user has read/write access to it.';
$string['backup_scheduledtime'] = 'Scheduled time';
$string['col_backupduration'] = 'Backup duration';
$string['col_completed'] = 'Completed?';
$string['col_categoryname'] = 'Category';
$string['col_checkbox'] = '';
$string['col_coursename'] = 'Course';
$string['col_courseurl'] = '';
$string['col_debuginfo'] = 'Debug';
$string['col_deleteurl'] = '';
$string['col_errorcode'] = 'Error Code';
$string['col_exception'] = 'Exception';
$string['col_fullname'] = 'Course Name';
$string['col_id'] = '';
$string['col_log'] = 'Log';
$string['col_message'] = 'Error Message';
$string['col_moreinfo'] = '';
$string['col_restore_categoryid'] = 'Category ID';
$string['col_restore_categoryname'] = 'Category name';
$string['col_restore_info'] = 'Info';
$string['col_restoreduration'] = 'Restore duration';
$string['col_shortname'] = 'Course Short Name';
$string['col_timecreated'] = 'Created time';
$string['col_timecompleted'] = 'Process completion time';
$string['col_timescheduled'] = 'Scheduled time';
$string['col_timestarted'] = 'Process start time';
$string['col_username'] = 'Username';
$string['confirm_delete_message'] = 'Are you sure you want to delete this transfer request?';
$string['courseselection_label'] = 'By course';
$string['courseselection_label_help'] = 'Select the courses you want to transfer with the same backup and restore settings';
$string['delete_backup'] = 'Click to delete this backup before it starts';
$string['error_course_id_not_exist'] = 'Error in the selected course list. One of the element does not exist in the course database';
$string['error_course_required'] = 'At least one course is required in order to process the transfer';
$string['error_delete_backup_not_exist'] = 'Error - The provided backup ID does not exist';
$string['error_delete_backup_started'] = 'Error - This backup transfer already started or is already completed. Impossible to delete.';
$string['error_do_transfer_not_allowed'] = 'You are not allowed to add a course transfer';
$string['error_get_backup_status'] = 'Failed to get backup status';
$string['error_get_restore_status'] = 'Failed to get restore status';
$string['error_log_not_allowed'] = 'You are not allowed to access the log page';
$string['error_no_backup'] = 'Incorrect ID provided. Please try again';
$string['error_no_restore'] = 'Incorrect ID provided. Please try again';
$string['formattime_hour'] = '{$a->hour} hour{$a->hs} ';
$string['formattime_min'] = '{$a->min} minute{$a->ms} ';
$string['formattime_sec'] = '{$a->sec} second{$a->ss}';
$string['host'] = 'Host server';
$string['host_help'] = 'The host server contains the courses that will be backed up and sent to a remote server to be restored. If this server is the host server, you must enter a backup folder path. Otherwise, leave blank.';
$string['loading_ajax'] = 'Loading...';
$string['course_transfer:do_transfer'] = 'Allow user to add a course transfer task';
$string['course_transfer:log_view'] = 'Allow user to view the log for the transfers';
$string['open_course'] = 'Click to open course in a new tab';
$string['page_add_transfer'] = 'Add a new Course Transfer';
$string['page_log'] = 'Course Transfer logs';
$string['remote'] = 'Remote server';
$string['remote_help'] = 'The remote server will restore the courses backed-up by the host server. If this server is the remote server, you must enter a restore folder path. Otherwise, leave blank.';
$string['remove_all_scheduled_backups'] = 'Delete all scheduled backups';
$string['remove_all_scheduled_backups_help'] = 'Are you sure you want to delete all scheduled back-ups. This cannot be undone.';
$string['restore_categoryid'] = 'Restore Category';
$string['restore_categoryid_help'] = 'Choose the category you want to restore in';
$string['restore_https'] = 'Restore server Web Services HTTPS?';
$string['restore_https_help'] = 'Check the box if the connexion is secured with HTTPS';
$string['restore_info'] = 'Restore info from "{$a}"';
$string['restore_path'] = 'Restore folder path';
$string['restore_path_help'] = 'Full path of the restore folder. Can be inside or outside moodledata folder as long as the Apache user has read/write access to it.';
$string['restore_server_name'] = 'Name of the Restore server to send the backups';
$string['restore_server_name_help'] = 'This name is only used for display purposes';
$string['restore_server'] = 'Restore server Web Services server address';
$string['restore_server_help'] = 'Address for the Restore server web services. Only the domain name, no http/https or path/folders (ex: eclass.yorku.ca)';
$string['restore_token'] = 'Restore server Web Services security token';
$string['restore_token_help'] = 'Add the security token required by the Restore server. This token allows the execution of the requests';
$string['search'] = 'Search';
$string['select_by_category'] = 'By category';
$string['select_by_category_help'] = 'Select a category if you want to add all courses from a specific category.';
$string['select_category'] = 'Select a category';
$string['select_completed_all'] = 'All';
$string['select_completed_0'] = 'Not yet';
$string['select_completed_1'] = 'Completed';
$string['select_completed_-1'] = 'Error/Failed';
$string['setting_add'] = 'Add a new course transfer';
$string['setting_logs'] = 'Transfer Logs';
$string['setting_menu'] = 'Course transfer';
$string['success_add_transfer'] = 'Successfully added {$a} courses to the transfer list';
$string['success_delete_transfer'] = 'Successfully deleted a transfer request';
$string['table_moreinfo'] = 'Click for more info';
$string['task_course_transfer'] = 'course_transfer - Scheduled task that will create backups and push restore on another server';
$string['title_backup_logs'] = 'Backup logs';
$string['title_backupoptions'] = 'Backup settings';
$string['title_courseselection'] = 'Course selection';
$string['title_restore_logs'] = 'Restore logs';
$string['title_restoreoptions'] = 'Restore settings';
$string['unenrol_users'] = 'Unenrol users from courses once restore is completed?';
$string['unenrol_users_help'] = 'Selecting yes will unenrol all users on this server from the courses selected above when the restore process is completed.';
$string['page_log_help'] = 'Check the logs for the completed transfers here:<a href="'.new moodle_url('/local/course_transfer/index.php').'">Transfer Logs</a>';
