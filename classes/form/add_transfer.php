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

namespace local_course_transfer\form;

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');


require_once($CFG->libdir . '/formslib.php');
require_once('HTML/QuickForm/input.php');

class add_transfer extends \moodleform {

    /**
     * Define the form - called by parent constructor.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'header_courseselection', get_string('title_courseselection', 'local_course_transfer'));
        $mform->addElement('html', '<hr>');
        //Select by category.
        $mform->addElement('select', 'localcategory', get_string('select_by_category', 'local_course_transfer'), $this->_customdata->local_categories, 0);
        $mform->addHelpButton('localcategory', 'select_by_category', 'local_course_transfer');

        $mform->addElement('html', '<hr>');

        $selected = '<span id="course_transfer_courseselection_selected"></span>';
        $mform->addElement('static', 'courseselection_label', get_string('courseselection_label', 'local_course_transfer'), $selected);
        $mform->addHelpButton('courseselection_label', 'courseselection_label', 'local_course_transfer');

        $mform->addElement('hidden', 'courseselection', '', array('id' => 'id_courseselection'));
        $mform->setType('courseselection', PARAM_TEXT);

        $mform->addElement('hidden', 'courseselectionhtml', '', array('id' => 'id_courseselectionhtml'));
        $mform->setType('courseselectionhtml', PARAM_RAW);
        $mform->addElement('html', $this->_customdata->courseselection);


        $mform->addElement('header', 'header_backupoptions', get_string('title_backupoptions', 'local_course_transfer'));
        $mform->setExpanded('header_backupoptions');

        //Add a scheduled time
        $mform->addElement('date_time_selector', 'timescheduled', get_string('backup_scheduledtime', 'local_course_transfer'));
        //The backup settings
        $mform->addElement('checkbox', 'users', get_string('backup_users', 'local_course_transfer'));
        $mform->addHelpButton('users', 'backup_users', 'local_course_transfer');
        $mform->addElement('checkbox', 'anonymize', get_string('backup_anonymize', 'local_course_transfer'));
        $mform->addHelpButton('anonymize', 'backup_anonymize', 'local_course_transfer');
        $mform->addElement('checkbox', 'role_assignments', get_string('backup_role_assignments', 'local_course_transfer'));
        $mform->addHelpButton('role_assignments', 'backup_role_assignments', 'local_course_transfer');
        $mform->addElement('checkbox', 'activities', get_string('backup_activities', 'local_course_transfer'));
        $mform->addHelpButton('activities', 'backup_activities', 'local_course_transfer');
        $mform->addElement('checkbox', 'blocks', get_string('backup_blocks', 'local_course_transfer'));
        $mform->addHelpButton('blocks', 'backup_blocks', 'local_course_transfer');
        $mform->addElement('checkbox', 'comments', get_string('backup_comments', 'local_course_transfer'));
        $mform->addHelpButton('comments', 'backup_comments', 'local_course_transfer');
        $mform->addElement('checkbox', 'badges', get_string('backup_badges', 'local_course_transfer'));
        $mform->addHelpButton('badges', 'backup_badges', 'local_course_transfer');
        $mform->addElement('checkbox', 'userscompletion', get_string('backup_userscompletion', 'local_course_transfer'));
        $mform->addHelpButton('userscompletion', 'backup_userscompletion', 'local_course_transfer');
        $mform->addElement('checkbox', 'logs', get_string('backup_logs', 'local_course_transfer'));
        $mform->addHelpButton('logs', 'backup_logs', 'local_course_transfer');
        $mform->addElement('checkbox', 'grade_histories', get_string('backup_grade_histories', 'local_course_transfer'));
        $mform->addHelpButton('grade_histories', 'backup_grade_histories', 'local_course_transfer');
        $mform->addElement('checkbox', 'questionbank', get_string('backup_questionbank', 'local_course_transfer'));
        $mform->addHelpButton('questionbank', 'backup_questionbank', 'local_course_transfer');

        //Rules disabled if
        $mform->disabledIf('anonymize', 'users', 'notchecked');
        $mform->disabledIf('role_assignments', 'users', 'notchecked');
        $mform->disabledIf('comments', 'users', 'notchecked');
        $mform->disabledIf('badges', 'users', 'notchecked');
        $mform->disabledIf('userscompletion', 'users', 'notchecked');
        $mform->disabledIf('logs', 'users', 'notchecked');
        $mform->disabledIf('grade_histories', 'users', 'notchecked');

        $mform->disabledIf('badges', 'activities', 'notchecked');
        $mform->disabledIf('grade_histories', 'activities', 'notchecked');

        $mform->addElement('header', 'header_restoreoptions', get_string('title_restoreoptions', 'local_course_transfer'));
        $mform->setExpanded('header_restoreoptions');
        
        $mform->addElement('selectyesno', 'unenrol_users', get_string('unenrol_users','local_course_transfer'));
        $mform->addHelpButton('unenrol_users', 'unenrol_users', 'local_course_transfer');
        $mform->setType('unenrol_users' , PARAM_INT);

        $mform->addElement('select', 'restore_categoryid', get_string('restore_categoryid', 'local_course_transfer'), $this->_customdata->categories);
        $mform->addHelpButton('restore_categoryid', 'restore_categoryid', 'local_course_transfer');
        $mform->addRule('restore_categoryid', null, 'required');


        $this->add_action_buttons(false, get_string('add_transfer_submit', 'local_course_transfer'));

        if ($this->_customdata->data) {
            $this->set_data($this->_customdata->data);
        }
    }

    /**
     * Perform minimal validation on the grade form
     * @global moodle_database $DB
     * @param array $data
     * @param array $files
     */
    public function validation($data, $files) {
        global $USER, $CFG, $DB;


        if ($errors = parent::validation($data, $files)) {
            return $errors;
        }
        //We need at least one course selected
        $courses = explode(',', $data['courseselection']);
        if ($data['localcategory'] == 0) {
            if ($data['courseselection'] == '' || count($courses) == 0) {
                $errors['courseselection_label'] = get_string('error_course_required', 'local_course_transfer');
            } else {
                $nbcourses = 0;
                foreach ($courses as $courseid) {
                    if (trim($courseid) != '' && (int) trim($courseid) != 0) {
                        $nbcourses++;
                        $course = $DB->get_record('course', array('id' => trim($courseid)));
                        if ($course == false) {
                            $errors['courseselection_label'] = get_string('error_course_id_not_exist', 'local_course_transfer');
                        }
                    }
                }
                if ($nbcourses == 0) {
                    $errors['courseselection_label'] = get_string('error_course_required', 'local_course_transfer');
                }
            }
        }


        return $errors;
    }

}
