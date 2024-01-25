<?php

ini_set('session.cookie_lifetime', 86400 * 365);
ini_set('session.gc_maxlifetime', 86400 * 365);
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function redirect($to) {
    header('Location: '.$to);
    exit;
}

function renderHTML($file) {
    $content = file_get_contents($file);
    echo $content;
}

function registerPerson($conn, $user) {
    $query = "INSERT IGNORE INTO personnes (name, Email, NumNational) VALUES (:name, :email, :numNational)";
    $sth = $conn->prepare($query);
    $sth->execute([ ':name' => $user['name'], ':email' => $user['mail'], ':numNational' => $user['number'] ]);
    $conn->commit();
}
