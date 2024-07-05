#!/bin/env php
<?php

require(__DIR__.'/../lib.php');

$env = new Env();
$givav = new Givav($env->config['remoteGivav']['login'], $env->config['remoteGivav']['password']);
echo "Authentification".PHP_EOL;
$givav->loginApp();
$givav->updateDebtors($env);
$env->mysql->commit();
echo "Done".PHP_EOL;
