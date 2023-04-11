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

    local_course_transfer_load_courseselection();

    $("#id_restore_categoryid").select2({width: 'resolve'});

});

/**
 * Load course selection table
 * @returns void
 */
function local_course_transfer_load_courseselection()
{
    var tableid = '#course_transfer_courseselection';
    var action = 'courseselection';


    //Create datatable settings
    var settings = local_course_transfer_table_default_settings(action);
    settings.select = {
        "style": 'multi',
        "selector": 'td:first-child'
    };
    settings.order = [[2, 'asc']];
    settings.pageLength = 15;
    settings.columns = [
        {
            "name": "checkbox",
            "class": "select-checkbox",
            "orderable": false,
            "searchable": false,
            "defaultContent": '',
            "width": "10px",
            "targets": 0
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
            "name": "fullname",
            "data": "fullname",
            "class": "col-fullname"
        },
        {
            "name": "shortname",
            "data": "shortname",
            "class": "col-shortname"
        },
        {
            "name": "categoryname",
            "data": "categoryname",
            "class": "col-categoryname"
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

    var $table = $(tableid);
    var $input_selected = $('#id_courseselection');
    var $input_selectedhtml = $('#id_courseselectionhtml');
    //Restore the possible values from the input selected
    $table.data('selectedcourses', {});
    //Load generic method for the table
    var datatable = local_course_transfer_load_table(tableid, action, settings);
    var $selected = $('#course_transfer_courseselection_selected');

    //On row select memory it
    datatable.on('select.dt', function(e, dt, type, indexes) {
        if (type !== 'row') {
            return;
        }
        var ids = datatable.rows(indexes).data().pluck('id');
        var usernames = datatable.rows(indexes).data().pluck('shortname');
        var selectedcourses = $table.data('selectedcourses');
        //Add the selected item to the selected IDs
        $.each(ids, function(i) {
            selectedcourses[ids[i]] = ids[i];
            if ($selected.find('[data-userid="' + ids[i] + '"]').length === 0) {
                $selected.append('<span data-userid="' + ids[i] + '" class="badge badge-info">' + usernames[i] + ' <span class="close" href="#">&times;</span></span> ');
                $input_selectedhtml.val($selected.html());
            }
        });
        $table.data('selectedcourses', selectedcourses);
        //Update the hidden field
        $input_selected.val(local_course_transfer_get_id_list(selectedcourses));
    });
    //On deselect remove it from the list
    datatable.on('deselect.dt', function(e, dt, type, indexes) {
        if (type !== 'row') {
            return;
        }
        var selectedcourses = $table.data('selectedcourses');
        var ids = datatable.rows(indexes).data().pluck('id');
        $.each(ids, function(i) {
            delete selectedcourses[ids[i]];
            $selected.find('[data-userid="' + ids[i] + '"]').remove();
            $input_selectedhtml.val($selected.html());
        });
        $table.data('selectedcourses', selectedcourses);
        //Update the hidden field
        $input_selected.val(local_course_transfer_get_id_list(selectedcourses));
    });
    //On draw, reselect previous rows
    datatable.on('draw.dt', function(e, settings) {
        var selectedcourses = $table.data('selectedcourses');
        var row;
        $.each(selectedcourses, function(i) {
            row = $('#' + i, $table);
            datatable.row(row).select();
        });
    });
    //On the close button for the badges, unselect
    $selected.on('click', '.badge .close', function() {
        var $item = $(this).parent();
        var userid = $item.data('userid');
        var row = $('#' + userid, $table);
        var selectedcourses = $table.data('selectedcourses');
        //If not found
        if (datatable.row(row).length > 0) {
            datatable.row(row).deselect();
        }
        else {
            delete selectedcourses[userid];
            $selected.find('[data-userid="' + userid + '"]').remove();
        }
    });

    datatable.on('init.dt', function(e, settings, json) {
        
        var nbloopsleft = 20;
        //In some case the select plugin is not yet loaded 
        //So we do a loop every 1/2 second if not loaded after 10 seconds, just stop here
        setTimeout(default_selected, 500);
        
        /**
         * Load the default selection for the table
         * @returns {void}
         */
        function default_selected() {
            if(typeof datatable.settings()[0]._select !== 'undefined') {
                var selectedcourses = {};
                var courseids = $input_selected.val().split(',');
                $.each(courseids, function(i){
                    if(!isNaN(courseids[i]) && courseids[i] !== '') {
                        selectedcourses[courseids[i]] = courseids[i];
                    }
                });
                $selected.append($input_selectedhtml.val());
                $table.data('selectedcourses', selectedcourses);
                datatable.draw();
            }
            else {
                if(nbloopsleft > 0) {
                    nbloopsleft--;
                    setTimeout(default_selected, 500);
                }
                else
                {
                    //clear the previous settings
                    $table.data('selectedcourses', {});
                    $input_selected.val('');
                }
            }
        }
    });
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
