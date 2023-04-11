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

namespace local_course_transfer\datatable;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once("$CFG->dirroot/local/course_transfer/classes/datatable/base.php");
require_once("$CFG->dirroot/local/course_transfer/lib.php");

class backup_log extends base
{

    /**
     * The default table for the sql
     * @var string
     */
    public $default_table = 'course_transfer_backup';

    public function __construct($draw, $length, $start, $sort, $search)
    {
        parent::__construct($draw, $length, $start, $sort, $search);
    }

    /**
     * Execute the request
     * @global \moodle_database $DB
     * @param \stdClass $where_search
     * @param string $order_by
     * @param int $limit_nbrows
     * @param int $limit_offset
     * @return array the data
     */
    protected function execute_request($where_search, $order_by, $limit_nbrows, $limit_offset)
    {
        global $DB;

        $data = array();

        $sql_where = '';
        $sql_params = array();


        //Construct the WHERE clause
        if ($where_search->sql != '')
        {
            $sql_where = 'WHERE ';
            $sql_where .= $where_search->sql;
            $sql_params = array_merge($sql_params, $where_search->params);
        }

        $sql_orderby = 'ORDER BY ';
        //Construct the ORDERBY clause
        if ($order_by != '')
        {
            $sql_orderby .= $order_by;
        }
        else
        {
            $sql_orderby .= 'tb.timescheduled DESC';
        }

        $sql = "SELECT tb.id, '' AS moreinfo, tb.timescheduled, 
                    CONCAT(c.fullname, ' - ', c.shortname) AS coursename,
                    tb.completed, IFNULL(tb.timecompleted, 0) - IFNULL(tb.timestarted, 0) AS backupduration,
                    '' AS courseurl, '' AS deleteurl,
                    tb.courseid
                FROM {course_transfer_backup} AS tb
                    JOIN {course} AS c ON c.id = tb.courseid
                $sql_where
                $sql_orderby
                ";

        $data = $DB->get_records_sql($sql, $sql_params, $limit_offset, $limit_nbrows);

        //Get total counts
        $this->recordsTotal = $this->get_count_total();

        //Get count filtered from the request
        $this->recordsFiltered = $this->get_count_filtered($sql, $sql_params);

        return $data;
    }

    /**
     * Format the returned data
     * @param array $data The array of data
     * @return array the formated data
     */
    protected function format_data($data)
    {
        global $CFG;

        $context = \context_system::instance();
        
        $formatted = array();
        foreach ($data as $row)
        {
            if ($row->timescheduled > 0)
            {
                $row->timescheduled = \date(self::$date_format, $row->timescheduled);
            }
            else
            {
                $row->timescheduled = ' - ';
            }

            //Get the course URL
            $courseurl = "$CFG->wwwroot/course/view.php?id=$row->courseid";
            $linktitle = get_string('open_course', 'local_course_transfer');
            $row->courseurl = "<a href=\"$courseurl\" title=\"$linktitle\" target=\"_blank\">";
            $row->courseurl .= "<i class=\"fa fa-link\"></i></a>";
            
            
            if(has_capability('local/course_transfer:do_transfer', $context) && ($row->completed == null || $row->completed == 0))
            {
                //Get the delete URL
                $deleteurl = "$CFG->wwwroot/local/course_transfer/delete_transfer.php?id=$row->id";
                $linktitle = get_string('delete_backup', 'local_course_transfer');
                $confirmmessage = get_string('confirm_delete_message', 'local_course_transfer');
                $jsconfirm = "return confirm('$confirmmessage')";
                $row->deleteurl = "<a href=\"$deleteurl\" title=\"$linktitle\" target=\"_blank\" onclick=\"$jsconfirm\">";
                $row->deleteurl .= "<i class=\"fa fa-times\"></i></a>";
            }

            //Format the backup duration
            if ($row->backupduration != 0)
            {
                $row->backupduration = \local_course_transfer_format_time(abs($row->backupduration));
            }

            //Completion status
            $row->completed = get_string('select_completed_' . ((int) $row->completed), 'local_course_transfer');

            $formatted[] = $row;
        }


        return $formatted;
    }

    /**
     * Prepare the where parameters 
     * @return stdClass the where params array, with $where->sql the where SQL and $where->params the array of parameters
     */
    public static function prepare_search_from_datatable($columns)
    {
        $where = new \stdClass();

        $where->sql = '';
        $where->params = array();

        // Create an array and we'll implode everything with an AND at the end
        $sql_array = array();

        foreach ($columns as $column)
        {
            $search = \trim($column['search']['value']);
            if ($search != '')
            {
                switch ($column['name'])
                {
                    case "coursename":
                        //We search for the course fullname and shortname and idnumber
                        $search_course = '%' . $search . '%';
                        $sql_array[] = '(c.fullname LIKE ? OR c.shortname LIKE ? OR c.idnumber LIKE ?)';
                        $where->params[] = $search_course;
                        $where->params[] = $search_course;
                        $where->params[] = $search_course;
                        break;
                    case "completed":
                        if ($search != 'all')
                        {
                            if ($search == 1)
                            {
                                $sql_array[] = 'tb.completed = ?';
                                $where->params[] = 1;
                            }
                            else if ($search == -1)
                            {
                                $sql_array[] = 'tb.completed < ?';
                                $where->params[] = 0;
                            }
                            else if ($search == 0)
                            {
                                $sql_array[] = '(tb.completed = ? OR tb.completed IS NULL)';
                                $where->params[] = 0;
                            }
                        }
                        break;
                }
            }
        }

        $where->sql = \implode(' AND ', $sql_array);

        return $where;
    }

    /**
     * Get the info for the backup
     * @global \moodle_database $DB
     * @param int $id
     * @return string
     */
    public static function format_data_display($id)
    {
        global $DB, $CFG;

        $html = '';


        //Get the backup info
        $backup = $DB->get_record('course_transfer_backup', array('id' => $id));
        //the backup does not exist
        if ($backup == false)
        {
            $html = get_string('error_no_backup', 'course_transfer');
            return $html;
        }

        //Username
        $user = $DB->get_record('user', array('id' => $backup->userid));
        $html .= '<div class="row-fluid">';
        $html .= '  <div class="col-md-2">';
        $html .= '      <b>' . get_string('col_username', 'local_course_transfer') . '</b>';
        $html .= '  </div>';
        $html .= '  <div class="col-md-10">';
        $html .= '  ' . fullname($user);
        $html .= '  </div>';
        $html .= '</div>';

        //Timecreated
        $html .= '<div class="row-fluid">';
        $html .= '  <div class="col-md-2">';
        $html .= '      <b>' . get_string('col_timecreated', 'local_course_transfer') . '</b>';
        $html .= '  </div>';
        $html .= '  <div class="col-md-10">';
        $html .= '  ' . \date(self::$date_format, $backup->timecreated);
        $html .= '  </div>';
        $html .= '</div>';
        //Timestarted
        $html .= '<div class="row-fluid">';
        $html .= '  <div class="col-md-2">';
        $html .= '      <b>' . get_string('col_timestarted', 'local_course_transfer') . '</b>';
        $html .= '  </div>';
        $html .= '  <div class="col-md-10">';
        $html .= '  ' . ($backup->timestarted > 0 ? \date(self::$date_format, $backup->timestarted) : ' - ');
        $html .= '  </div>';
        $html .= '</div>';
        //Timecompleted
        $html .= '<div class="row-fluid">';
        $html .= '  <div class="col-md-2">';
        $html .= '      <b>' . get_string('col_timecompleted', 'local_course_transfer') . '</b>';
        $html .= '  </div>';
        $html .= '  <div class="col-md-10">';
        $html .= '  ' . ($backup->timecompleted > 0 ? \date(self::$date_format, $backup->timecompleted) : ' - ');
        $html .= '  </div>';
        $html .= '</div>';
        //Restore category ID
        $html .= '<div class="row-fluid">';
        $html .= '  <div class="col-md-2">';
        $html .= '      <b>' . get_string('col_restore_categoryid', 'local_course_transfer') . '</b>';
        $html .= '  </div>';
        $html .= '  <div class="col-md-10">';
        $html .= '  ' . $backup->restore_categoryid;
        $html .= '  </div>';
        $html .= '</div>';

        //If there is an error during the import display it here
        if ($backup->completed < 0)
        {
            $html .= '<div class="row-fluid">';
            $html .= '  <div class="col-md-2">';
            $html .= '      <b>' . get_string('col_log', 'local_course_transfer') . '</b>';
            $html .= '  </div>';
            $html .= '  <div class="col-md-10">';
            $html .= '      <pre>';
            $html .= '  ' . $backup->log;
            $html .= '      </pre>';
            $html .= '  </div>';
            $html .= '</div>';
        }

        //Now we want to grab the restore information if it exists
        if ($backup->restore_restoreid != null)
        {
            $html .= '      <h3>' . get_string('restore_info', 'local_course_transfer', $CFG->local_course_transfer_restore_server_name) . '</h3>';

            $restore_params = array();
            $restore_params['restoreid'] = $backup->restore_restoreid;
            $restorestatus = local_course_transfer_execute_ws_call('course_transfer_get_restore_status', $restore_params);


            //Error with the call
            if ($restorestatus->code != '200 OK' || $restorestatus->return_object === null || 
                    !is_object($restorestatus->return_object))
            {
                $html .= '<div class="row-fluid">';
                $html .= '  <div class="col-md-2">';
                $html .= get_string('error_get_restore_status', 'local_course_transfer');
                $html .= '  </div>';
                $html .= '  <div class="col-md-10">';
                $html .= '      <pre>';
                $html .= '  ' . var_export($restorestatus, true);
                $html .= '      </pre>';
                $html .= '  </div>';
                $html .= '</div>';
            }
            else
            {
                //Print each element
                foreach($restorestatus->return_object as $key => $value)
                {
                    $html .= '<div class="row-fluid">';
                    $html .= '  <div class="col-md-2">';
                    $html .= get_string("col_$key", 'local_course_transfer');
                    $html .= '  </div>';
                    $html .= '  <div class="col-md-10">';
                    $html .= '  ' . $value;
                    $html .= '  </div>';
                    $html .= '</div>';
                }
            }
        }

        return $html;
    }

}
