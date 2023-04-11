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

class course extends base
{

    /**
     * The default table for the sql
     * @var string
     */
    public $default_table = 'course';

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

        $sql = "SELECT c.id, '' AS checkbox, c.shortname, 
                    c.fullname,
                    ca.name AS categoryname,
                    '' AS courseurl,
                    c.id AS courseid
                FROM {course} AS c
                    JOIN {course_categories} AS ca ON ca.id = c.category
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

            //Get the course URL
            $courseurl = "$CFG->wwwroot/course/view.php?id=$row->courseid";
            $linktitle = get_string('open_course', 'local_course_transfer');
            $row->courseurl = "<a href=\"$courseurl\" title=\"$linktitle\", target=\"_blank\">";
            $row->courseurl .= "<i class=\"fa fa-link\"></i></a>";

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
                    case "fullname":
                        $search_course = '%' . $search . '%';
                        $sql_array[] = '(c.fullname LIKE ?)';
                        $where->params[] = $search_course;
                        break;
                    case "shortname":
                        $search_course = '%' . $search . '%';
                        $sql_array[] = '(c.shortname LIKE ?)';
                        $where->params[] = $search_course;
                        break;
                    case "categoryname":
                        $search_course = '%' . $search . '%';
                        $sql_array[] = '(ca.name LIKE ?)';
                        $where->params[] = $search_course;
                        break;
                }
            }
        }

        $where->sql = \implode(' AND ', $sql_array);

        return $where;
    }

}
