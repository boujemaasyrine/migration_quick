<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 30/06/2016
 * Time: 12:13
 */


$url = "http://monsite.com/api ​ /exemple/service"; 
$secret_key = "123456"; 
$data = array ( 
    "key1" => "value1", 
    "key(n)" => "value(n)", 
);
$curl = curl_init(); 
curl_setopt($curl, CURLOPT_URL, $url); 
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
    'Api-User: quick',
    'Api-hash: 2f6d70d20d23e782c8fc4637934090bda49eca80')
);