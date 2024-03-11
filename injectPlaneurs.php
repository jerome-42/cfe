<?php

include_once __DIR__ . '/env.php';
include_once __DIR__ . '/givav.php';
include_once __DIR__ . '/vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('UTC');

$env = new Env();
$options = getopt('u:h');
if (isset($options['h'])) {
    fprintf(STDERR, "usage: ".$argv[0].' -u numéro national ou email'.PHP_EOL);
    exit(0);
}
if (!isset($options['u'])) {
    fprintf(STDERR, '-u is mandatory'.PHP_EOL);
    exit(1);
}

$user = $options['u'];
echo "Votre mot de passe pour ".$user.": ";
$password = fgets(STDIN);
if ($password === '') {
    fprintf(STDERR, "le mot de passe ne peut être vide".PHP_EOL);
    exit(1);
}

// try to login
try {
    $givav = new Givav($user, $password);
    $givav->login();
    $planeurs = $givav->getGliders();
}
catch (Exception $e) {
    fprintf(STDERR, "erreur: ".$e->getMessage().PHP_EOL);
    exit(1);
}
$q = "INSERT INTO planeurs (immat, concours, modele) VALUES (:immat, :concours, :modele) ON DUPLICATE KEY UPDATE concours = :concours, modele = :modele";
$sth = $env->mysql->prepare($q);
foreach ($planeurs as $planeur) {
    $sth->execute([
        ':immat' => $planeur['immat'],
        ':concours' => $planeur['concours'],
        ':modele' => $planeur['modele'],
    ]);
}
$env->mysql->commit();
