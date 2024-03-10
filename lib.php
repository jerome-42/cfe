<?php

include_once __DIR__ . '/env.php';
include_once __DIR__ . '/vendor/autoload.php';

ini_set('session.cookie_lifetime', 86400 * 365);
ini_set('session.gc_maxlifetime', 86400 * 365);
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('UTC');

function getSessionKey() {
    if (!isset($_SESSION['signKey']))
        $_SESSION['signKey'] = randomString(16);
    return $_SESSION['signKey'];
}

function getYear() {
    return intval(date('Y'));
}

function exportAllData_getYears($conn) {
    $query = "SELECT CAST(replace(what, 'defaultCFE_TODO_', '') AS SIGNED) AS year FROM settings WHERE what LIKE 'defaultCFE_TODO_%'";
    $sth = $conn->prepare($query);
    $sth->execute([ ]);
    if ($sth->rowCount() === 0)
        return [ getYear() ];
    $data = $sth->fetchAll();
    return array_map(function($line) {
        return $line['year'];
    }, $data);
}

function exportAllData_getPersonnes($conn, $year) {
    $fd = fopen('php://temp/maxmemory:1048576', 'w');
    if ($fd === false) {
        throw new Exception('Failed to open temporary file');
    }
    $membres = Personne::getAll($conn, $year);
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

function exportAllData_getRecords($conn, $year) {
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
    $records = $cfe->getAllRecords($year);
    if (count($records) === 0)
        return '';
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

function getClientIP() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

function parseDateDDMMAAAA($date) {
    $d = explode(' ', $date);
    $elem = explode('/', $d[0]);
    if (count($elem) != 3)
        throw new Exception($date." n'est pas une date au format DDMMAAAA");
    if (count($d) == 1)
        return ['year' => $elem[2], 'month' => $elem[1], 'day' => $elem[0] ];
    else
        return ['year' => $elem[2], 'month' => $elem[1], 'day' => $elem[0], 'time' => $d[1] ];
}

function randomString($length) {
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $pass = [];
    $alphaLength = strlen($alphabet) - 1;
    for ($i = 0; $i < $length; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass);
}

function redirect($to) {
    header('Location: '.$to);
    return;
}

function renderHTML($file) {
    $content = file_get_contents($file);
    echo $content;
}
