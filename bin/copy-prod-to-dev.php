#!/usr/bin/env php
<?php

require_once(__DIR__.'/../lib.php');

$config = json_decode(file_get_contents(__DIR__.'/../config.json'), true);
$dsn = join(';', [ 'host='.$config['database']['host'], 'dbname='.$config['database']['database'] ]);
$conn = new PDO("mysql:".$dsn, $config['database']['username'], $config['database']['password']);
checkDatabase($conn, $config['database']['database']);
$conn->beginTransaction();

$tables = [ 'cfe_records', 'cfe_todo', 'flarm_logs', 'glider', 'personnes', 'settings' ];
foreach ($tables as $table) {
    $query = "TRUNCATE TABLE ".$table;
    $conn->query($query);
    $query = "INSERT INTO ".$table." SELECT * FROM cfe.".$table;
    $conn->query($query);
}
