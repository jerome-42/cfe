<?php

include_once __DIR__ . '/db.php';
include_once __DIR__ . '/vendor/autoload.php';

ini_set('session.cookie_lifetime', 86400 * 365);
ini_set('session.gc_maxlifetime', 86400 * 365);
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('UTC');

function getYear() {
    return intval(date('Y'));
}

function exportAllData_getPersonnes($conn) {
    $fd = fopen('php://temp/maxmemory:1048576', 'w');
    if ($fd === false) {
        throw new Exception('Failed to open temporary file');
    }
    $membres = Personne::getAll($conn);
    $columns = [ [ 'givavNumber', 'givav' ], [ 'name', 'nom' ], [ 'email', 'email' ],
                 [ 'isAdmin', 'administrateur' ], [ 'cfeTODO', 'cfe' ] ];
    $headers = array_map(function($i) {
        return $i[1];
    }, $columns);
    fputcsv($fd, $headers);
    foreach ($membres as $membre) {
        $line = [];
        foreach ($columns as $column) {
            if (isset($membre[$column[0]]))
                $line[] = $membre[$column[0]];
            else
                $line[] = '';
        }
        fputcsv($fd, $line);
    }
    rewind($fd);
    $csv = stream_get_contents($fd);
    fclose($fd);
    return $csv;
}

function exportAllData_getRecords($conn) {
    $fd = fopen('php://temp/maxmemory:1048576', 'w');
    if ($fd === false) {
        throw new Exception('Failed to open temporary file');
    }
    $cfe = new CFE($conn, null);
    $columns = [ [ 'who', 'givav' ], [ 'registerDate', 'date enregistrement' ],
                 [ 'workDate', 'date CFE' ], [ 'workType', 'type' ],
                 [ 'beneficiary', 'beneficiaire' ], [ 'duration', 'durée' ],
                 [ 'details', 'détails' ],
                 [ 'status', 'statut' ], [ 'statusDate', 'date de validation' ],
                 [ 'rejectedCause', 'rejet' ],
                 [ 'statusWho', 'validation par' ] ];
    $headers = array_map(function($i) {
        return $i[1];
    }, $columns);
    fputcsv($fd, $headers);
    $records = $cfe->getAllRecords();
    foreach ($records as $record) {
        $line = [];
        foreach ($columns as $column) {
            if (isset($record[$column[0]]))
                $line[] = $record[$column[0]];
            else
                $line[] = '';
        }
        fputcsv($fd, $line);
    }
    rewind($fd);
    $csv = stream_get_contents($fd);
    fclose($fd);
    return $csv;
}

function redirect($to) {
    header('Location: '.$to);
    return;
}

function renderHTML($file) {
    $content = file_get_contents($file);
    echo $content;
}
