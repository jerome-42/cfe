<?php

include_once __DIR__ . '/db.php';

class Env {
    public $mysql;
    public $config;
    private $pug;

    public function __construct() {
        $this->config = json_decode(file_get_contents(__DIR__.'/config.json'), true);
        $dsn = join(';', [ 'host='.$this->config['database']['host'], 'dbname='.$this->config['database']['database'] ]);
        $this->mysql = new PDO("mysql:".$dsn, $this->config['database']['username'], $this->config['database']['password']);
        checkDatabase($this->mysql, $this->config['database']['database']);
        $this->mysql->query("SET time_zone = 'Europe/Paris'");
        $this->mysql->beginTransaction();
        return $this;
    }

    public function initPug() {
        $this->pug = new Pug([
            'cache' => __DIR__.'/../cache/',
            'debug' => true,
            'pretty' => true,
        ]);
        $this->pug->share('durationToHuman', function($text) {
            $hours = floor(intval($text) / 60);
            $minutes = intval($text) % 60;
            if ($hours == 0 && $minutes == 0)
                return "0 minute";
            $ret = [];
            if ($hours >= 2)
                $ret[] = $hours." heures";
            else if ($hours == 1)
                $ret[] = "1 heure";
            if ($minutes > 1)
                $ret[] = $minutes." minutes";
            else if ($minutes == 1)
                $ret[] = "1 minute";
            return join(' ', $ret);
        });
        return $this->pug;
    }
}
