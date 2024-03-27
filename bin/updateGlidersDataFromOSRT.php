#!/bin/env php
<?php

require_once(__DIR__.'/../lib.php');

$env = new Env();
$gliders = new Gliders($env->mysql);
$gliders->updateDataFromOSRT($env->config['osrt'], $env->mysql, true);
$env->mysql->commit();
//DEBUG $osrt = new OSRT();
//DEBUG $osrt->getDetailsFromImmatParseHTML(file_get_contents('test'));
