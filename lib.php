<?php

include_once __DIR__ . '/vendor/autoload.php';

ini_set('session.cookie_lifetime', 86400 * 365);
ini_set('session.gc_maxlifetime', 86400 * 365);
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('UTC');

function redirect($to) {
    header('Location: '.$to);
    exit;
}

function renderHTML($file) {
    $content = file_get_contents($file);
    echo $content;
}
