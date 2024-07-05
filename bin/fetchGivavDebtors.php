#!/bin/env php
<?php

require(__DIR__.'/../lib.php');

$env = new Env();
$givav = new Givav($env->config['remoteGivav']['login'], $env->config['remoteGivav']['password']);
echo "Authentification".PHP_EOL;
$givav->loginApp();
$debtors = $givav->fetchDebtors();
$q = "INSERT INTO givavdebtor (givavNumber, balance, since, lastUpdate) VALUES (:givavNumber, :balance, NOW(), NOW()) ON DUPLICATE KEY UPDATE balance = :balance, lastUpdate = NOW()";
$sth = $env->mysql->prepare($q);
$givavNumbers = [];
foreach ($debtors as $debt) {
    $sth->execute([ ':givavNumber' => $debt['givavNumber'], ':balance' => $debt['balance'] ]);
}
$env->mysql->query("DELETE FROM givavdebtor WHERE lastUpdate != CAST(NOW() AS date)");
$env->mysql->commit();
echo "Done".PHP_EOL;
