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
$plugins = array(
    'jstree' => array('files' => array(
            'jstree/jstree.js',
            'jstree/themes/default/style.css',
        )),
    'datatable' => array('files' => array(
            'datatable/js/jquery.dataTables.js',
            'datatable/js/dataTables.select.js',
            'datatable/js/dataTables.bootstrap.js',
            'datatable/css/jquery.dataTables.css',
            'datatable/css/select.dataTables.css',
            'datatable/css/dataTables.bootstrap.css',
            'datatable/css/dataTables.fontAwesome.css',
        )),
    'select2' => array('files' => array(
            'select2/js/select2.full.js',
            'select2/css/select2.css'
        )),
);
