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
function display_page() {
    // CHECK And PREPARE DATA
    global $DB, $PAGE, $OUTPUT, $CFG;


    //Context System
    $context = context_system::instance();

    require_login(1, false);

    //Javascript css requires
    $PAGE->requires->jquery();
    $PAGE->requires->jquery_plugin('ui');
    $PAGE->requires->jquery_plugin('ui-css');
    $PAGE->requires->jquery_plugin('datatable', 'local_course_transfer');
    $PAGE->requires->js('/local/course_transfer/js/lib.js');
    $PAGE->requires->js('/local/course_transfer/js/log.js');
    $PAGE->requires->css('/local/course_transfer/styles.css');
    //Strings useds in the Javascript
    $PAGE->requires->strings_for_js(array('table_moreinfo', 'loading_ajax', 'remove_all_scheduled_backups_help'), 'local_course_transfer');

    //Check the capability
    if (!has_capability('local/course_transfer:log_view', $context)) {
        redirect('/', get_string('error_log_not_allowed', 'local_course_transfer'), 10);
        return;
    }

    //Prepare content
    //Table for Backups
    $columns = array();
    $columns[] = 'moreinfo';
    $columns[] = 'id';
    $columns[] = 'timescheduled';
    $columns[] = 'coursename';
    $columns[] = 'completed';
    $columns[] = 'backupduration';
    $columns[] = 'courseurl';
    $columns[] = 'deleteurl';

    $colsearch = array();
    $colsearch[] = 'coursename';
    $colsearch[] = 'completed';

    $select_completed = array();
    $select_completed['all'] = get_string('select_completed_all', 'local_course_transfer');
    $select_completed[1] = get_string('select_completed_1', 'local_course_transfer');
    $select_completed[0] = get_string('select_completed_0', 'local_course_transfer');
    $select_completed[-1] = get_string('select_completed_-1', 'local_course_transfer');

    $table_header = '';
    $table_footer = '';
    $filter_prefix = 'backup_';
    foreach ($columns as $i => $column) {
        $table_header .= "<th class=\"header_$column\">";
        $title = get_string('col_' . $column, 'local_course_transfer');
        $table_header .= $title;
        $table_header .= '</th>';
        $table_footer .= "<th class=\"header_$column\">";
        //Add a search input
        if (array_search($column, $colsearch) !== false) {
            //Completed gets a different search with a select
            if ($column != 'completed') {
                $title = get_string('search', 'local_course_transfer') . " $title";
                $table_footer .= "<input type=\"text\" id=\"filter_$filter_prefix$column\" class=\"filter_datatable\" ";
                $table_footer .= " tabindex=\"100$i\" placeholder=\"$title\" data-colname=\"$column\" autocomplete=\"off\"/>";
            } else {
                $table_footer .= "<select id=\"filter_$filter_prefix$column\" class=\"filter_datatable\" data-colname=\"$column\" tabindex=\"100$i\" autocomplete=\"off\">";
                foreach ($select_completed as $key => $name) {
                    $table_footer .= "<option value=\"$key\">$name</option>";
                }
                $table_footer .= "</select>";
            }
        }
        $table_footer .= '</th>';
    }

    $backuptable = '';

    //Create the table
    $backuptable .= '<table id="course_transfer_backup" class="course_transfer_datatable stripe hover order-column compact cell-border row-border" width="100%">';
    $backuptable .= '  <thead>';
    $backuptable .= $table_header;
    $backuptable .= '  </thead>';
    $backuptable .= '  <tfoot>';
    $backuptable .= $table_footer;
    $backuptable .= '  </tfoot>';
    $backuptable .= '</table>';


    //Table for Restore
    $columns = array();
    $columns[] = 'moreinfo';
    $columns[] = 'id';
    $columns[] = 'timecreated';
    $columns[] = 'categoryname';
    $columns[] = 'coursename';
    $columns[] = 'completed';
    $columns[] = 'restoreduration';
    $columns[] = 'courseurl';

    $colsearch = array();
    $colsearch[] = 'coursename';
    $colsearch[] = 'categoryname';
    $colsearch[] = 'completed';

    $table_header = '';
    $table_footer = '';
    $filter_prefix = 'restore_';
    foreach ($columns as $i => $column) {
        $table_header .= "<th class=\"header_$column\">";
        $title = get_string('col_' . $column, 'local_course_transfer');
        $table_header .= $title;
        $table_header .= '</th>';
        $table_footer .= "<th class=\"header_$column\">";
        //Add a search input
        if (array_search($column, $colsearch) !== false) {
            //Completed gets a different search with a select
            if ($column != 'completed') {
                $title = get_string('search', 'local_course_transfer') . " $title";
                $table_footer .= "<input type=\"text\" id=\"filter_$filter_prefix$column\" class=\"filter_datatable\" ";
                $table_footer .= " tabindex=\"200$i\" placeholder=\"$title\" data-colname=\"$column\" autocomplete=\"off\"/>";
            } else {
                $table_footer .= "<select id=\"filter_$filter_prefix$column\" class=\"filter_datatable\" data-colname=\"$column\" tabindex=\"200$i\" autocomplete=\"off\">";
                foreach ($select_completed as $key => $name) {
                    $table_footer .= "<option value=\"$key\">$name</option>";
                }
                $table_footer .= "</select>";
            }
        }
        $table_footer .= '</th>';
    }

    $restoretable = '';

    //Create the table
    $restoretable .= '<table id="course_transfer_restore" class="course_transfer_datatable stripe hover order-column compact cell-border row-border" width="100%">';
    $restoretable .= '  <thead>';
    $restoretable .= $table_header;
    $restoretable .= '  </thead>';
    $restoretable .= '  <tfoot>';
    $restoretable .= $table_footer;
    $restoretable .= '  </tfoot>';
    $restoretable .= '</table>';


    $add_link = '';
    if (has_capability('local/course_transfer:do_transfer', $context)) {
        $add_link .= "<a href=\"$CFG->wwwroot/local/course_transfer/add_transfer.php\" class=\"btn btn-outline-primary\">";
//        $add_link .= '<i class="fa fa-plus"></i> ';
        $add_link .= get_string('add_new_transfer_process', 'local_course_transfer');
        $add_link .= "</a>";
        
        $remove_all_scheduled_backups = "<a href=\"JavaScript:Void(0);\" class=\"btn btn-outline-danger ml-2\" onClick=\"local_course_remove_backups()\">"; 
        $remove_all_scheduled_backups .= get_string('remove_all_scheduled_backups', 'local_course_transfer');; 
        $remove_all_scheduled_backups .= "</a>"; 
    }

    
    //Content
    $html_content = '';

    $html_content .= $add_link . $remove_all_scheduled_backups;
    $html_content .= '<h2>';
    $html_content .= get_string('title_backup_logs', 'local_course_transfer');
    $html_content .= '</h2>';
    $html_content .= $backuptable;
    $html_content .= '<h2>';
    $html_content .= get_string('title_restore_logs', 'local_course_transfer');
    $html_content .= '</h2>';
    $html_content .= $restoretable;
    $html_content .= $add_link;





    //**********************
    //*** DISPLAY HEADER ***
    //**********************
    $titlepage = get_string('page_log', 'local_course_transfer');
    $PAGE->set_url("$CFG->wwwroot/local/course_transfer/index.php");
    $PAGE->set_title($titlepage);
    $PAGE->set_heading($titlepage);
    $PAGE->set_pagelayout('admin');
    $PAGE->set_context($context);

    //Create Breadcrumbs

    echo $OUTPUT->header();

    //***********************
    //*** DISPLAY CONTENT ***
    //***********************

    echo $OUTPUT->box($html_content, 'generalbox center clearfix page_index');

    //**********************
    //*** DISPLAY FOOTER ***
    //**********************
    echo $OUTPUT->footer();
}

display_page();

