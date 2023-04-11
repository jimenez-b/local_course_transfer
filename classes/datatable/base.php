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
 * *************************************************************************/
namespace local_course_transfer\datatable;
defined('MOODLE_INTERNAL') || die;

abstract class base
{

    /**
     * The draw number for the request
     * @var int
     */
    public $draw = 1;

    /**
     * The Total number of results
     * @var int
     */
    public $recordsTotal = 0;

    /**
     * The number of results with the filter
     * @var int
     */
    public $recordsFiltered = 0;
    /**
     * The default string format for the date display
     * @var string
     */
    public static $date_format = 'Y-m-d H:i:s';

    /**
     * The data result
     * @var array
     */
    public $data = array();
    
    /**
     * The default table for the sql
     * @var string
     */
    public $default_table = '';

    public function __construct($draw, $length, $start, $sort, $search)
    {
        $this->draw = $draw;

        $data = $this->execute_request($search, $sort, $length, $start);

        $this->data = $this->format_data($data);
    }

    /**
     * Return the total number of results without any filter
     * @global \moodle_database $DB
     * @return int The total number of results
     */
    protected function get_count_total()
    {
        global $DB;

        $sql = "SELECT count(*) AS nbrows FROM {{$this->default_table}}";

        $result = $DB->get_record_sql($sql);

        return $result->nbrows;
    }
    
    /**
     * Return the total number of results with the filter
     * @global \moodle_database $DB
     * @param string the SQL for to get the elements
     * @param array $sql_params The array of params for the SQL
     * @return int The total number of results
     */
    protected function get_count_filtered($sql, $sql_params)
    {
        global $DB;

        $filtered_count = $DB->get_record_sql("SELECT count(*) AS records_filtered FROM ($sql) AS r", $sql_params);
        //Get count filtered from the request
        $recordsFiltered = $filtered_count->records_filtered;

        return $recordsFiltered;
    }
        
    /**
     * Return the object in JSON
     * @return string
     */
    public function return_json()
    {
        return json_encode($this);
    }

    /**
     * Execute the request
     * @global \moodle_database $DB
     * @param type $where_search
     * @param type $order_by
     * @param type $limit_nbrows
     * @param type $limit_offset
     * @return array the data
     */
    protected abstract function execute_request($where_search, $order_by, $limit_nbrows, $limit_offset);

    /**
     * Format the returned data
     * @param array $data The array of data
     * @return array the formated data
     */
    protected abstract function format_data($data);


}
