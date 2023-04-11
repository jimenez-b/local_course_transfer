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

// We defined the web service functions to install.
$functions = array(
    'course_transfer_restore_request' => array(
        'classname' => 'local_course_transfer_external',
        'methodname' => 'restore_request',
        'classpath' => 'local/course_transfer/externallib.php',
        'description' => 'Create a restore request in the database a course can be restored',
        'type' => 'write',
    ),
    'course_transfer_get_categories' => array(
        'classname' => 'local_course_transfer_external',
        'methodname' => 'get_categories',
        'classpath' => 'local/course_transfer/externallib.php',
        'description' => 'Return the list of categories',
        'type' => 'read',
    ),
    'course_transfer_get_backup_status' => array(
        'classname' => 'local_course_transfer_external',
        'methodname' => 'get_backup_status',
        'classpath' => 'local/course_transfer/externallib.php',
        'description' => 'Return the backup information from the restore server',
        'type' => 'read',
    ),
    'course_transfer_get_restore_status' => array(
        'classname' => 'local_course_transfer_external',
        'methodname' => 'get_restore_status',
        'classpath' => 'local/course_transfer/externallib.php',
        'description' => 'Return the restore information from the backup server',
        'type' => 'read',
    )
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
    'Course Transfer Service' => array(
        'shortname' => 'local_course_transfer',
        'functions' => array(
            'course_transfer_restore_request',
            'course_transfer_get_categories',
            'course_transfer_get_backup_status',
            'course_transfer_get_restore_status'
        ),
        'restrictedusers' => 0,
        'enabled' => 1,
    )
);
