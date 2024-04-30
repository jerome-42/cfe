#!/bin/env php
<?php

require_once(__DIR__.'/../lib.php');

$env = new Env();
$ogn = new OGN();
$immat = $ogn->getGliderImmatFromRadioId($argv[1]);
var_dump($immat);
