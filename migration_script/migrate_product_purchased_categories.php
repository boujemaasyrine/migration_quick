<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 16/03/2016
 * Time: 15:36
 */

if(count($argv) === 5) {
    $dbh = new PDO("pgsql:dbname=$argv[1];
                           host=$argv[2]",
                           $argv[3]
                           ,$argv[4]);

    // copy product
    $sql = "CREATE TABLE product2 AS TABLE product;";
    $stm = $dbh->prepare($sql);
    $stm->execute();

    // execute shcmea update
    $consoleDir = __DIR__."/../app/console ";
    echo `php $consoleDir doctrine:schema:update --force`;

    // restore categorie
    $sql2 = "UPDATE product_purchased SET product_category_id = P2.product_category_id FROM product2 P2 WHERE P2.id = product_purchased.id;";
    $stm = $dbh->prepare($sql2);
    $stm->execute();
    $sql2 = "UPDATE product_purchased SET external_id = P2.external_id FROM product2 P2 WHERE P2.id = product_purchased.id;";
    $stm = $dbh->prepare($sql2);
    $stm->execute();

    // drop copied table
    $sql3 = "DROP TABLE product2;";
    $stm = $dbh->prepare($sql3);
    $stm->execute();
}
