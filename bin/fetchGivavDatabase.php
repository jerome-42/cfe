#!/bin/env php
<?php

require(__DIR__.'/../givav.php');

if (count($argv) === 1) {
    fprintf(STDERR, "usage: ".$argv[0]." [ fetch | clean | inject | truncate ]\n");
    exit(1);
}
if (in_array($argv[1], [ 'fetch', 'clean', 'inject', 'truncate' ]) === false) {
    fprintf(STDERR, "usage: ".$argv[0]." [ fetch | clean | inject | truncate ]\n");
    exit(1);
}

$config = json_decode(file_get_contents(__DIR__.'/../config.json'), true);

switch ($argv[1]) {
case "fetch":
    $givav = new Givav($config['remoteGivav']['login'], $config['remoteGivav']['password']);
    echo "Authentification".PHP_EOL;
    $givav->loginApp();
    $backupFilename = $givav->downloadBackup();
    if (file_exists($backupFilename) === false)
        throw new Exception("Pas de fichier téléchargé depuis GIVAV");
    $sqlFilename = $backupFilename.'.sql';
    echo "Décompression".PHP_EOL;
    system('/usr/bin/zstd -d '.$backupFilename.' -o '.$sqlFilename);
    if (file_exists($sqlFilename) === false)
        throw new Exception("La décompression de ".$backupFilename." s'est mal passée");
    echo "SQL file is ".$sqlFilename." next type: $ ".$argv[0]." clean && ".$argv[0]." inject ".$sqlFilename."\n";
    exit;

case "clean":
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
    exit;

case "inject":
    // restauration
    if (count($argv) !== 3) {
        fprintf(STDERR, "usage: ".$argv[0]." [fetch | clean | inject backup.sql | truncate\n");
        exit(1);
    }
    echo "Restauration".PHP_EOL;
    $sqlFilename = $argv[2];
    if (file_exists($sqlFilename) === false)
        throw new Exception("Le fichier ".$sqlFilename." n'existe pas");
    system('/usr/bin/psql -q -U givav -h 127.0.0.1 givav < '.$sqlFilename);

    //unlink($sqlFilename);
    //unlink($backupFilename);

    // injection des procédures stockées
    system('/usr/bin/psql -q -U givav -h 127.0.0.1 givav < '.__DIR__.'/../private-data/sql.sql');
    exit;

case "truncate":
    if (count($argv) !== 3) {
        fprintf(STDERR, "usage: ".$argv[0]." [fetch | clean | inject | truncate 2024-08-31\n");
        exit(1);
    }
    if (preg_match_all('/^\d{4}-\d{2}-\d{2}$/', $argv[2], $matches) === false || count($matches[0]) === 0) {
        fprintf(STDERR, "la date ".$argv[2]." n'est pas lisible".PHP_EOL);
        fprintf(STDERR, "usage: ".$argv[0]." [fetch | clean | inject | truncate 2024-08-31\n");
        exit(1);
    }
    $d = date_parse_from_format('Y-m-d', $argv[2]);
    if (count($d['warnings']) !== 0) {
        fprintf(STDERR, "la date ".$argv[2]." n'est pas lisible".PHP_EOL);
        fprintf(STDERR, "usage: ".$argv[0]." [fetch | clean | inject | truncate 2024-08-31\n");
        exit(1);
    }
    $dsn = join(';', [ 'host='.$config['givav']['host'], 'dbname='.$config['givav']['database'] ]);
    $db = new PDO("pgsql:".$dsn, $config['givav']['username'], $config['givav']['password']);
    $d = $argv[2];
    echo "Truncate newer flights and billings lines after ".$d.PHP_EOL;
    $queries = [ "DELETE FROM vol WHERE date_vol > :d", "DELETE FROM cp_piece WHERE date_piece > :d" ];
    foreach ($queries as $q) {
        $sth = $db->prepare($q);
        $sth->execute([ ':d' => $d ]);
    }
    exit;
}
