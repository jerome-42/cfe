#!/bin/env php
<?php

require_once(__DIR__.'/../lib.php');

$env = new Env();
$gliders = new Gliders($env->mysql);
$gliders->updateDataFromOSRT($env->config['osrt'], $env->mysql, true);
//$osrt = new OSRT($env->mysql);
//DEBUG $osrt->fromRoleGetGlidersParseHTML(file_get_contents('test'));
//DEBUG var_dump($osrt->getDetailsFromImmatParseHTML(file_get_contents('F-CCJX'), 'F-CCJX'));
$env->mysql->commit();
