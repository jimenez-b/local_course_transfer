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
require_once("$CFG->dirroot/local/course_transfer/classes/form/add_transfer.php");
require_once("$CFG->dirroot/local/course_transfer/lib.php");

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


    //Context System
    $context = context_system::instance();

    require_login(1, false);

    //Javascript css requires
    $PAGE->requires->jquery();
    $PAGE->requires->jquery_plugin('ui');
    $PAGE->requires->jquery_plugin('ui-css');
    $PAGE->requires->jquery_plugin('datatable', 'local_course_transfer');
    $PAGE->requires->jquery_plugin('select2', 'local_course_transfer');
    $PAGE->requires->js('/local/course_transfer/js/lib.js');
    $PAGE->requires->js('/local/course_transfer/js/transfer.js');
    $PAGE->requires->css('/local/course_transfer/styles.css');
    //Strings useds in the Javascript
    $PAGE->requires->strings_for_js(array('table_moreinfo', 'loading_ajax'), 'local_course_transfer');

    //Check the capability
    if (!has_capability('local/course_transfer:do_transfer', $context)) {
        redirect('/', get_string('error_do_transfer_not_allowed', 'local_course_transfer'), 10);
        return;
    }

    //Prepare content
    //Table for Backups
    $columns = array();
    $columns[] = 'checkbox';
    $columns[] = 'id';
    $columns[] = 'fullname';
    $columns[] = 'shortname';
    $columns[] = 'categoryname';
    $columns[] = 'courseurl';

    $colsearch = array();
    $colsearch[] = 'fullname';
    $colsearch[] = 'shortname';
    $colsearch[] = 'categoryname';

    $table_header = '';
    $table_footer = '';
    $filter_prefix = 'courseselection_';
    foreach ($columns as $i => $column) {
        $table_header .= "<th class=\"header_$column\">";
        $title = get_string('col_' . $column, 'local_course_transfer');
        $table_header .= $title;
        $table_header .= '</th>';
        $table_footer .= "<th class=\"header_$column\">";
        //Add a search input
        if (array_search($column, $colsearch) !== false) {
            $title = get_string('search', 'local_course_transfer') . " $title";
            $table_footer .= "<input type=\"text\" id=\"filter_$filter_prefix$column\" class=\"filter_datatable\" ";
            $table_footer .= " tabindex=\"100$i\" placeholder=\"$title\" data-colname=\"$column\" autocomplete=\"off\"/>";
        }
        $table_footer .= '</th>';
    }

    $table = '';

    //Create the table
    $table .= '<table id="course_transfer_courseselection" class="course_transfer_datatable stripe hover order-column compact cell-border row-border" width="100%">';
    $table .= '  <thead>';
    $table .= $table_header;
    $table .= '  </thead>';
    $table .= '  <tfoot>';
    $table .= $table_footer;
    $table .= '  </tfoot>';
    $table .= '</table>';

    //Restore category list
    $categories = array('' => get_string('select_category', 'local_course_transfer'));
    $categories_call = local_course_transfer_execute_ws_call('course_transfer_get_categories', array());
    //Error with the call
    if ($categories_call->code != '200 OK' || $categories_call->return_object === null ||
            !is_object($categories_call->return_object)) {
        foreach ($categories_call->return_object as $category) {
            $categories[$category->id] = $category->name;
        }
    }


    $timescheduled = new DateTime();
    $timescheduled->setTime($CFG->local_course_transfer_backup_default_time_h, $CFG->local_course_transfer_backup_default_time_m, 0);
    //If timeschedule is < now add 24 hours
    if ($timescheduled->getTimestamp() < time()) {
        $timescheduled->add(new DateInterval('P1D'));
    }

    //Get all categories
    $categories_list = [
        0 => get_string('select')
    ];
    $core_categories_list = \core_course_category::make_categories_list();
    foreach ($core_categories_list as $key => $category) {
        $categories_list[$key] = $category;
    }

    $prepared_data = new stdClass();
    //Course table
    $prepared_data->courseselection = $table;
    //Restore categories
    $prepared_data->categories = $categories;
    //Local categories
    $prepared_data->local_categories = $categories_list;

    //Form default data
    $prepared_data->data = new stdClass();
    $prepared_data->data->users = false;
    $prepared_data->data->anonymize = false;
    $prepared_data->data->role_assignments = true;
    $prepared_data->data->activities = true;
    $prepared_data->data->blocks = true;
    $prepared_data->data->comments = true;
    $prepared_data->data->badges = true;
    $prepared_data->data->userscompletion = false;
    $prepared_data->data->logs = false;
    $prepared_data->data->grade_histories = false;
    $prepared_data->data->questionbank = true;
    $prepared_data->data->timescheduled = $timescheduled->getTimestamp();
    $prepared_data->data->unenrol_users = true;

    //Content
    $html_content = '';

    $mform = new \local_course_transfer\form\add_transfer(null, $prepared_data);

    //If form cancelled
    if ($mform->is_cancelled()) {//Form cancelled
        redirect('/');
        die;
    } else if ($dataform = $mform->get_data()) { //Save the form data submitted
        //Default settings for the backup process
        $backup_settings = new stdClass();
        $backup_settings->users = 0;
        $backup_settings->anonymize = 0;
        $backup_settings->role_assignments = 0;
        $backup_settings->activities = 0;
        $backup_settings->blocks = 0;
        $backup_settings->comments = 0;
        $backup_settings->badges = 0;
        $backup_settings->role_userscompletion = 0;
        $backup_settings->logs = 0;
        $backup_settings->grade_histories = 0;
        $backup_settings->questionbank = 0;

        //Default config for all backup info
        $backup_default_config = new stdClass();
        $backup_default_config->courseid = null;
        $backup_default_config->userid = $USER->id;
        $backup_default_config->settings = $backup_settings;
        $backup_default_config->timecreated = time();
        $backup_default_config->timescheduled = $dataform->timescheduled;
        $backup_default_config->completed = null;
        $backup_default_config->log = '';
        $backup_default_config->timestated = null;
        $backup_default_config->timecompleted = null;
        $backup_default_config->restore_settings = new stdClass();
        $backup_default_config->restore_categoryid = $dataform->restore_categoryid;
        $backup_default_config->restore_restoreid = null;
        $backup_default_config->unenrol_users = $dataform->unenrol_users;
        foreach ($backup_settings as $key => $value) {
            if (isset($dataform->$key) && $dataform->$key == 1) {
                $backup_default_config->settings->$key = 1;
            }
        }
        //If we anonymize add the information to the restore settings
        if ($backup_default_config->settings->anonymize) {
            $backup_default_config->restore_settings->anonymize = 1;
        }

        $nbinfo = 0;
        $courseids = explode(',', $dataform->courseselection);
        

        // If category has been selected, loop through and add all courses
        // Also loop through all subcategories
        $category_course_ids = [];
        if ($dataform->localcategory) {            
            $i = 0;
            // Get courses in category
            $courses = $DB->get_records('course', ['category' => $dataform->localcategory], 'id', 'id');
            foreach ($courses as $course) {
                $category_course_ids[$i] = $course->id;
                $i++;
            }
        }
        
        // Merge all course id arrays        
        $all_course_ids = array_merge($courseids, $category_course_ids);

        //Now loop on the courses to add an info per course
        foreach ($all_course_ids as $courseid) {
            $courseid = (int) trim($courseid);
            $course = $DB->get_record('course', array('id' => $courseid));

            if ($course == false) {
                continue;
            }

            $backup_info = clone($backup_default_config);

            $backup_info->courseid = $course->id;

            $backup_info->restore_settings->course_fullname = $course->fullname;
            $backup_info->restore_settings->course_shortname = $course->shortname;

            $backup_info->settings = json_encode($backup_info->settings);
            $backup_info->restore_settings = json_encode($backup_info->restore_settings);

            $DB->insert_record('course_transfer_backup', $backup_info);

            $nbinfo++;
        }


        $PAGE->set_url("$CFG->wwwroot/local/course_transfer/index.php");
        redirect(new moodle_url('/local/course_transfer/index.php'), get_string('success_add_transfer', 'local_course_transfer', $nbinfo), 10);
    }




    //**********************
    //*** DISPLAY HEADER ***
    //**********************
    $titlepage = get_string('page_add_transfer', 'local_course_transfer');
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

    echo $OUTPUT->box_start('generalbox center clearfix page_add_transfer');

    //Display form
    if (is_object($mform)) {
        $mform->display();
    } else {
        echo $html_content;
    }
    echo $OUTPUT->box_end();

    //**********************
    //*** DISPLAY FOOTER ***
    //**********************
    echo $OUTPUT->footer();
}

display_page();

