<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 29/09/2016
 * Time: 11:40
 */

if (isset($_SERVER['HTTP_CLIENT_IP'])
    || isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    || !(in_array(@$_SERVER['REMOTE_ADDR'], array('127.0.0.1', 'fe80::1', '::1')) || php_sapi_name() === 'cli-server')
) {
    header('HTTP/1.0 403 Forbidden');
    exit('You are not allowed to access this file. Check '.basename(__FILE__).' for more information.');
}

if (file_exists(".htaccess.old")) {
    echo "Can't start maintenace, there's already a htaccess old";
} else {
    copy('.htaccess', '.htaccess.old');
    unlink('.htaccess');
    copy('.htaccess.maintenance', '.htaccess');
}

