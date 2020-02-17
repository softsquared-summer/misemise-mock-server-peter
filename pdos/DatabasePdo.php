<?php

//DB 정보
function pdoSqlConnect()
{
    try {
        $DB_HOST = "15.164.183.104";
        $DB_NAME = "miseDB";
        $DB_USER = "mise";
        $DB_PW = "mise123!";
        $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME", $DB_USER, $DB_PW);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (\Exception $e) {
        echo $e->getMessage();
    }
}