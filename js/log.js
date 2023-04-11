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
 * *************************************************************************/

$(function() {

    local_course_transfer_load_backup_log();
    local_course_transfer_load_restore_log();

});

/**
 * Load Backup log table
 * @returns void
 */
function local_course_transfer_load_backup_log()
{
    var tableid = '#course_transfer_backup';
    var action = 'backup_log';


    //Create datatable settings
    var settings = local_course_transfer_table_default_settings(action);
    settings.columns = [
        {
            "class": "col-moreinfo",
            "orderable": false,
            "searchable": false,
            "defaultContent": '<i class="fa fa-plus-square" title="' + M.util.get_string('table_moreinfo', 'local_course_transfer') + '"></i>',
            "data": "",
            "width": "3px"
        },
        {
            "name": "id",
            "data": "id",
            "class": "col-id",
            "visible": false,
            "orderable": false,
            "searchable": false
        },
        {
            "name": "timescheduled",
            "data": "timescheduled",
            "class": "col-timescheduled"
        },
        {
            "name": "coursename",
            "data": "coursename",
            "class": "col-coursename"
        },
        {
            "name": "completed",
            "data": "completed",
            "class": "col-completed",
            "width": "80px"
        },
        {
            "name": "backupduration",
            "data": "backupduration",
            "class": "col-backupduration"
        },
        {
            "name": "courseurl",
            "data": "courseurl",
            "class": "col-courseurl",
            "width": "5px",
            "orderable": false,
            "searchable": false
        },
        {
            "name": "deleteurl",
            "data": "deleteurl",
            "class": "col-deleteurl",
            "width": "5px",
            "orderable": false,
            "searchable": false
        }
    ];

    //Load generic method for the table
    local_course_transfer_load_table(tableid, action, settings);
}

/**
 * Load Restore log table
 * @returns void
 */
function local_course_transfer_load_restore_log()
{
    var tableid = '#course_transfer_restore';
    var action = 'restore_log';


    //Create datatable settings
    var settings = local_course_transfer_table_default_settings(action);
    settings.columns = [
        {
            "class": "col-moreinfo",
            "orderable": false,
            "searchable": false,
            "defaultContent": '<i class="fa fa-plus-square" title="' + M.util.get_string('table_moreinfo', 'local_course_transfer') + '"></i>',
            "data": "",
            "width": "3px"
        },
        {
            "name": "id",
            "data": "id",
            "class": "col-id",
            "visible": false,
            "orderable": false,
            "searchable": false
        },
        {
            "name": "timecreated",
            "data": "timecreated",
            "class": "col-timecreated"
        },
        {
            "name": "categoryname",
            "data": "categoryname",
            "class": "col-categoryname"
        },
        {
            "name": "coursename",
            "data": "coursename",
            "class": "col-coursename"
        },
        {
            "name": "completed",
            "data": "completed",
            "class": "col-completed",
            "width": "80px"
        },
        {
            "name": "restoreduration",
            "data": "restoreduration",
            "class": "col-restoreduration"
        },
        {
            "name": "courseurl",
            "data": "courseurl",
            "class": "col-courseurl",
            "width": "5px",
            "orderable": false,
            "searchable": false
        }
    ];

    //Load generic method for the table
    local_course_transfer_load_table(tableid, action, settings);
}
