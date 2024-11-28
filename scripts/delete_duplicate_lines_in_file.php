<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 18/05/2016
 * Time: 09:14
 */
echo 'Start ';
if (count($argv) === 2) {
    $filePath = $argv[1];
    $lines = file($filePath);
    $lines = array_unique($lines);
    file_put_contents($filePath, implode($lines));
}
echo 'End';

