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

namespace local_course_transfer\datatable;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once("$CFG->dirroot/local/course_transfer/classes/datatable/base.php");
require_once("$CFG->dirroot/local/course_transfer/lib.php");

class restore_log extends base
{

    /**
     * The default table for the sql
     * @var string
     */
    public $default_table = 'course_transfer_restor';

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

        //CONU - COALESCE is a more compatible function than IFNULL
        //replacing IFNULL for COALESCE
        $sql = "SELECT tr.id, '' AS moreinfo, tr.timecreated, 
                    CONCAT(c.fullname, ' - ', c.shortname) AS coursename,
                    ca.name AS categoryname,
                    tr.completed, COALESCE(tr.timecompleted, 0) - COALESCE(tr.timestarted, 0) AS restoreduration,
                    '' AS courseurl,
                    tr.courseid
                FROM {course_transfer_restor} AS tr
                    LEFT JOIN {course} AS c ON c.id = tr.courseid
                    JOIN {course_categories} AS ca ON ca.id = tr.categoryid
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

        $formatted = array();
        foreach ($data as $row)
        {
            if ($row->timecreated > 0)
            {
                $row->timecreated = \date(self::$date_format, $row->timecreated);
            }
            else
            {
                $row->timecreated = ' - ';
            }

            $row->courseurl = '';
            if($row->completed == 1)
            {
                //Get the course URL
                $courseurl = "$CFG->wwwroot/course/view.php?id=$row->courseid";
                $linktitle = get_string('open_course', 'local_course_transfer');
                $row->courseurl = "<a href=\"$courseurl\" title=\"$linktitle\", target=\"_blank\">";
                $row->courseurl .= "<i class=\"fa fa-link\"></i></a>";
            }

            //Format the restore duration
            if ($row->restoreduration != 0 && $row->completed == 1)
            {
                $row->restoreduration = \local_course_transfer_format_time(abs($row->restoreduration));
            }
            else
            {
                $row->restoreduration = " - ";
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
                                $sql_array[] = 'tr.completed = ?';
                                $where->params[] = 1;
                            }
                            else if ($search == -1)
                            {
                                $sql_array[] = 'tr.completed < ?';
                                $where->params[] = 0;
                            }
                            else if ($search == 0)
                            {
                                $sql_array[] = '(tr.completed = ? OR tr.completed IS NULL)';
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
     * Get the info for the restore
     * @global \moodle_database $DB
     * @param int $id
     * @return string
     */
    public static function format_data_display($id)
    {
        global $DB, $CFG;

        $html = '';


        //Get the restore info
        $restore = $DB->get_record('course_transfer_restor', array('id' => $id));
        //the backup does not exist
        if ($restore == false)
        {
            $html = get_string('error_no_restore', 'course_transfer');
            return $html;
        }

        //Timecreated
        $html .= '<div class="row-fluid">';
        $html .= '  <div class="col-md-2">';
        $html .= '      <b>' . get_string('col_timecreated', 'local_course_transfer') . '</b>';
        $html .= '  </div>';
        $html .= '  <div class="col-md-10">';
        $html .= '  ' . \date(self::$date_format, $restore->timecreated);
        $html .= '  </div>';
        $html .= '</div>';
        //Timestarted
        $html .= '<div class="row-fluid">';
        $html .= '  <div class="col-md-2">';
        $html .= '      <b>' . get_string('col_timestarted', 'local_course_transfer') . '</b>';
        $html .= '  </div>';
        $html .= '  <div class="col-md-10">';
        $html .= '  ' . ($restore->timestarted > 0 ? \date(self::$date_format, $restore->timestarted) : ' - ');
        $html .= '  </div>';
        $html .= '</div>';
        //Timecompleted
        $html .= '<div class="row-fluid">';
        $html .= '  <div class="col-md-2">';
        $html .= '      <b>' . get_string('col_timecompleted', 'local_course_transfer') . '</b>';
        $html .= '  </div>';
        $html .= '  <div class="col-md-10">';
        $html .= '  ' . ($restore->timecompleted > 0 ? \date(self::$date_format, $restore->timecompleted) : ' - ');
        $html .= '  </div>';
        $html .= '</div>';

        //If there is an error during the restore display it here
        if ($restore->completed < 0)
        {
            $html .= '<div class="row-fluid">';
            $html .= '  <div class="col-md-2">';
            $html .= '      <b>' . get_string('col_log', 'local_course_transfer') . '</b>';
            $html .= '  </div>';
            $html .= '  <div class="col-md-10">';
            $html .= '      <pre>';
            $html .= '  ' . $restore->log;
            $html .= '      </pre>';
            $html .= '  </div>';
            $html .= '</div>';
        }

        //Now we want to grab the restore information if it exists
        if ($restore->backup_backupid != null)
        {
            $html .= '      <h3>' . get_string('backup_info', 'local_course_transfer', $CFG->local_course_transfer_restore_server_name) . '</h3>';

            $backup_params = array();
            $backup_params['backupid'] = $restore->backup_backupid;
            $backupstatus = local_course_transfer_execute_ws_call('course_transfer_get_backup_status', $backup_params);


            //Error with the call
            if ($backupstatus->code != '200 OK' || $backupstatus->return_object === null || 
                    !is_object($backupstatus->return_object))
            {
                $html .= '<div class="row-fluid">';
                $html .= '  <div class="col-md-2">';
                $html .= get_string('error_get_backup_status', 'local_course_transfer');
                $html .= '  </div>';
                $html .= '  <div class="col-md-10">';
                $html .= '      <pre>';
                $html .= '  ' . var_export($backupstatus, true);
                $html .= '      </pre>';
                $html .= '  </div>';
                $html .= '</div>';
            }
            else
            {
                //Print each element
                foreach($backupstatus->return_object as $key => $value)
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
