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
 * *************************************************************************
 * ************************************************************************ */

$tasks = array(
    array(
        'classname' => 'local_course_transfer\task\course_transfer',
        'blocking' => 0,
        'minute' => '*/20',
        'hour' => '18-23',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    )
);
