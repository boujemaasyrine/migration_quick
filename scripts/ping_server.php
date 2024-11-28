<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 02/01/2017
 * Time: 10:41
 */

$consoleDir = __DIR__."../app/console ";

if(!isset($argv[1]) || !isset($argv[0])){
    echo "Arguments not founds.\n";
    exit;
}

echo "Ping host $argv[1] \n";

$ip = $argv[1];
$user = $argv[2];
$ping = exec("ping -c 1 -s 64 -t 64 ".$ip);

if($ping){
    echo "Host $ip is alive..\n";
} else {
    echo "Host $ip is down!\n";
    echo `php $consoleDir quick:email:server:down --env=prod`;
    echo `sh ./server_up.sh $user`;
    echo `php $consoleDir quick:email:server:up --env=prod`;
};