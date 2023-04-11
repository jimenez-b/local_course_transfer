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
 * *************************************************************************
 * ************************************************************************ */

/**
 * Return the URL for the Restore server web service funtion
 * @global stdClass $CFG
 * @param string $function
 * @return string
 */
function local_course_transfer_get_ws_url() {
    global $CFG;

    $url = '';

    //Add the http/https
    $url .= 'http';
    if ($CFG->local_course_transfer_restore_https == true) {
        $url .= 's';
    }
    $url .= '://';

    //Add the domain
    $url .= $CFG->local_course_transfer_restore_server;

    //Add the fixed path
    $url .= "/webservice/rest/server.php";

    return $url;
}

/**
 * Execute a request and return the answer
 * @global stdClass $CFG
 * @param string $function The function name to execute
 * @param array $params The parameters to send
 * @return \stdClass
 */
function local_course_transfer_execute_ws_call($function, $params) {
    global $CFG;

    //Include curl class to connect to the web service.
    require_once("$CFG->libdir/filelib.php");

    //Token
    $token = $CFG->local_course_transfer_restore_token;

    $url = local_course_transfer_get_ws_url();

    $request_object = new stdClass();

    //Create the request object
    $request_object->request = new curl();

    $params['wstoken'] = $token;
    $params['wsfunction'] = $function;
    $params['moodlewsrestformat'] = 'json';

    $params_string = '';
    foreach ($params as $key => $value) {
        $value = urlencode($value);
        $params_string .= "$key=$value&";
    }

    //Request options
    $options = array('CURLOPT_POST' => count($params));

    //Execute the request
    $request_object->response = $request_object->request->post($url, $params_string, $options);

    //Check result
    $request_object->answer = $request_object->request->getResponse();

    $request_object->return = false;

    $request_object->return_object = null;
    $request_object->code = '';
    if (isset($request_object->answer['HTTP/1.1'])) {
        $request_object->code = $request_object->answer['HTTP/1.1'];
    }

    if (isset($request_object->answer['HTTP/1.1']) && $request_object->answer['HTTP/1.1'] == '200 OK') {
        $request_object->return_object = json_decode($request_object->response);
    }

    return $request_object;
}

/**
 * Update the navigation block with options
 * @global moodle_database $DB
 * @global stdClass $USER
 * @global stdClass $CFG
 * @global moodle_page $PAGE
 * @param settings_navigation $settingsnav The settings navigation block
 */
function local_course_transfer_extend_settings_navigation(settings_navigation $settingsnav) {
    global $CFG, $PAGE, $COURSE;

    //Get the system Context
    $context = context_system::instance();
    if (has_capability('local/course_transfer:log_view', $context)) {
        $node = $settingsnav->find('local_course_transfer_menu', navigation_node::TYPE_CONTAINER);
        if (!$node) {
            $node_title = get_string('setting_menu', 'local_course_transfer');
            $node = $settingsnav->add($node_title, null, navigation_node::TYPE_CONTAINER, $node_title, 'local_course_transfer_menu');
        }
        //Log menu
        $node_title = get_string('setting_logs', 'local_course_transfer');
        $node_url = new moodle_url('/local/course_transfer/index.php');
        $node->add($node_title, $node_url, navigation_node::TYPE_SETTING, $node_title, 'local_course_transfer_log');
        $node->showinflatnavigation = true;
        //Add menu
        if (has_capability('local/course_transfer:do_transfer', $context)) {
            $node_title = get_string('setting_add', 'local_course_transfer');
            $node_url = new moodle_url('/local/course_transfer/add_transfer.php');
            $node->add($node_title, $node_url, navigation_node::TYPE_SETTING, $node_title, 'local_course_transfer_add');
            $node->showinflatnavigation = true;
        }
    }
}

/**
 * Update the navigation block  - USED for retro compatibility Moodle < 3.0
 * @global moodle_database $DB
 * @global stdClass $USER
 * @global stdClass $CFG
 * @global moodle_page $PAGE
 * @param settings_navigation $settingsnav The settings navigation block
 */
function local_course_transfer_extend_navigation(global_navigation $navigation) {
    //Get the system Context
    $context = context_system::instance();
    if (has_capability('local/course_transfer:log_view', $context)) {
        $node = $navigation->find('local_course_transfer_menu', navigation_node::TYPE_CONTAINER);
        if (!$node) {
            $node_title = get_string('setting_menu', 'local_course_transfer');
            $node = $navigation->add($node_title, new moodle_url('/local/course_transfer/index.php'), navigation_node::TYPE_CONTAINER, $node_title, 'local_course_transfer_menu',new pix_icon('i/export', ''));
            $node->showinflatnavigation = true;
        }
    }
}

/**
 * Format time 
 * @param int $time
 * @return string
 */
function local_course_transfer_format_time($time) {
    $returntime = '';
    //Less than a minute
    if ($time < 60) {
        $sec = $time;
        $params = array('sec' => $sec);
        $params['ss'] = ($sec > 1 ? 's' : '');
        $returntime = get_string('formattime_sec', 'local_course_transfer', $params);
    }//Less than an hour
    else if ($time < 60 * 60) {
        $min = \floor($time / 60);
        $sec = $time - ($min * 60);
        $params = array('sec' => $sec, 'min' => $min);
        $params['ms'] = ($min > 1 ? 's' : '');
        $params['ss'] = ($sec > 1 ? 's' : '');
        $returntime = get_string('formattime_min', 'local_course_transfer', $params);
        if ($sec > 0) {
            $returntime .= get_string('formattime_sec', 'local_course_transfer', $params);
        }
    }//In hours
    else {
        $hour = floor($time / 3600);
        $min = floor(($time - ($hour * 3600)) / 60);
        $sec = $time - ($hour * 3600 + $min * 60);
        $params = array('sec' => $sec, 'min' => $min, 'hour' => $hour);
        $params['hs'] = ($hour > 1 ? 's' : '');
        $params['ms'] = ($min > 1 ? 's' : '');
        $params['ss'] = ($sec > 1 ? 's' : '');
        $returntime = get_string('formattime_hour', 'local_course_transfer', $params);
        if ($min > 0) {
            $returntime .= get_string('formattime_min', 'local_course_transfer', $params);
        }
        if ($sec > 0) {
            $returntime .= get_string('formattime_sec', 'local_course_transfer', $params);
        }
    }

    return trim($returntime);
}
