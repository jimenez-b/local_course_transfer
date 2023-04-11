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

/**
 * Load Backup log table
 * @param {string} tableid
 * @param {string} action
 * @param {object} settings
 * @returns object The datatable if needed after
 */
function local_course_transfer_load_table(tableid, action, settings)
{

    //Create a data for the backup logs
    var $table = $(tableid);
    //The datatable
    var datatable = $table.DataTable(settings);
    // Add event listener for opening and closing details
    $('tbody', $table).on('click', 'td.col-moreinfo', function () {
        var tr = $(this).closest('tr');
        var row = datatable.row(tr);
        if (row.child.isShown()) {
            //Change the icon to +
            $('i', this).addClass('fa-plus-square').removeClass('fa-minus-square');
            // This row is already open - close it
            row.child.hide();
            tr.removeClass('shown');
        } else {
            //Change the icon to -
            $('i', this).addClass('fa-minus-square').removeClass('fa-plus-square');
            //Check if moreinfo_cache exists and is filled
            if (typeof row.data().moreinfo_cache === 'undefined')
            {
                //moreinfo not loaded so request an ajax info
                var infocontent = $('<div></div>');
                //Temporary loading image
                local_course_transfer_loading(infocontent);
                row.child(infocontent).show();
                tr.addClass('shown');
                //Get the info in ajax
                $.ajax({
                    'method': "GET",
                    'url': M.cfg.wwwroot + "/local/course_transfer/ajax_load_content.php",
                    'data': {"action": action + "_moreinfo", "id": row.data().id},
                    'success': function (data) {
                        row.data().moreinfo_cache = data;
                        row.child(data);
                    }
                });
            } else
            {
                //Get directly the info from the cache
                // Open this row
                row.child(row.data().moreinfo_cache).show();
                tr.addClass('shown');
            }
        }
    });
    /**
     * Event temporary function
     * @returns {void}
     */
    var eventfunction = function () {
        clearTimeout($.data(this, 'timer'));
        var wait = setTimeout(local_course_transfer_search, 500, $table, datatable);
        $(this).data('timer', wait);
    };
    //Add event on the searches
    $table.on('keyup', 'input.filter_datatable', eventfunction);
    $table.on('change', 'select.filter_datatable', eventfunction);
    return datatable;
}

/**
 * Return the default settings for the datatable
 * @param {string} action
 * @returns {object}
 */
function local_course_transfer_table_default_settings(action)
{
    var settings = {
        "processing": true,
        "serverSide": true,
        "dom": 'rtip',
        "ajax": M.cfg.wwwroot + "/local/course_transfer/ajax_load_content.php?action=" + action,
        "order": [[2, 'desc']],
        "pageLength": 25,
        "rowId": 'id',
        "deferRender": true,
        "columns": []
    };
    return settings;
}

/**
 * Refresh the datatable with this values
 * @param {node} $table
 * @param {object} datatable
 * @returns {void}
 */
function local_course_transfer_search($table, datatable)
{
    $('.filter_datatable', $table).each(function () {
        var value = $(this).val();
        datatable.column($(this).data('colname') + ':name').search(value);
    });
    datatable.draw();
}

/**
 * Display a loading image in a specified block
 * @param $parent_block object
 * @returns void
 */
function local_course_transfer_loading($parent_block)
{
    var src = M.util.image_url('i/loading_small', 'moodle');
    var alt = M.util.get_string('loading_ajax', 'local_course_transfer');
    var $loading_img = $('<img id="loading-img" src="' + src + '" alt="' + alt + '"/>');
    $parent_block.append($loading_img);
}

/**
 * Return the list of ids from a object
 * @param objectIds {object}
 * @returns {String}
 */
function local_course_transfer_get_id_list(objectIds)
{
    var idlist = [];
    $.each(objectIds, function (i) {
        idlist.push(i);
    });
    return idlist.join(',');
}

function local_course_remove_backups() {
    let result = confirm(M.util.get_string('remove_all_scheduled_backups_help', 'local_course_transfer'));

    if (result) {
        $.ajax({
            async: true,
            type: 'GET',
            url: M.cfg.wwwroot + '/local/course_transfer/ajax_remove_backups.php?',
            success: function (response) {
                location.reload(); 
            }
        });
    }
}