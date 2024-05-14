#!/bin/env php
<?php

require(__DIR__.'/../givav.php');

$config = json_decode(file_get_contents(__DIR__.'/../config.json'), true);
$givav = new Givav($config['remoteGivav']['login'], $config['remoteGivav']['password']);
echo "Authentification".PHP_EOL;
$givav->loginApp();
$backupFilename = $givav->downloadBackup();
$sqlFilename = $backupFilename.'.sql';
echo "Décompression".PHP_EOL;
system('/usr/bin/bzcat -d '.$backupFilename.' > '.$sqlFilename);
echo "DROP DATABASE".PHP_EOL;
$dsn = join(';', [ 'host='.$config['givav']['host'], 'dbname='.$config['givav']['database'] ]);
$db = new PDO("pgsql:".$dsn, $config['givav']['username'], $config['givav']['password']);

// views
$q = "SELECT 'DROP VIEW IF EXISTS \"' || viewname || '\" CASCADE;' AS q FROM pg_views WHERE schemaname = 'public'";
$sth = $db->prepare($q);
$lines = $sth->fetchAll(PDO::FETCH_ASSOC);
foreach ($lines as $line) {
    $sth = $db->prepare($line['q']);
    $sth->execute();
}

// tables
$q = "SELECT 'DROP TABLE IF EXISTS \"' || tablename || '\" CASCADE;' AS q FROM pg_tables WHERE schemaname = 'public'";
$sth = $db->prepare($q);
$sth->execute();
$lines = $sth->fetchAll(PDO::FETCH_ASSOC);
foreach ($lines as $line) {
    $sth = $db->prepare($line['q']);
    $sth->execute();
}

// séquences
$q = "SELECT 'DROP SEQUENCE IF EXISTS \"' || relname || '\" CASCADE;' AS q FROM pg_class WHERE relkind = 'S'";
$sth = $db->prepare($q);
$sth->execute();
$lines = $sth->fetchAll(PDO::FETCH_ASSOC);
foreach ($lines as $line) {
    $sth = $db->prepare($line['q']);
    $sth->execute();
}

// restauration
echo "Restauration".PHP_EOL;
system('/usr/bin/psql -q -U givav -h 127.0.0.1 givav < '.$sqlFilename);

unlink($sqlFilename);
unlink($backupFilename);

// injection des procédures stockées
system('/usr/bin/psql -q -U givav -h 127.0.0.1 givav < '.__DIR__.'/../private-data/sql.sql');
