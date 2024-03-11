<?php

include_once __DIR__ . '/db.php';
include_once __DIR__ . '/parametres.php';
include_once __DIR__ . '/planeurs.php';

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
        $this->pug->share('getMembreNameByGivavNumber', function($text) {
            if (is_numeric($text) === false)
                return 'NA';
            $id = intval($text);
            $q = "SELECT name FROM personnes WHERE givavNumber = :givavNumber";
            $sth = $this->mysql->prepare($q);
            $sth->execute([ ':givavNumber' => $id ]);
            $name = $sth->fetchAll(PDO::FETCH_ASSOC)[0]['name'];
            return $name;
        });
        $this->pug->share('timestampToDate', function($text) {
            if (is_numeric($text) === false)
                return 'NA';
            return date('d/m/Y', intval($text));
        });
        $this->pug->share('estUneBonneVersionFlarm', function($version) {
            $parametres = new Parametres($this->mysql);
            $goodFlarmVersion = explode("\n", $parametres->get('flarmBonnesVersions', ''));
            $goodFlarmVersion = array_map('trim', $goodFlarmVersion);
            return in_array($version, $goodFlarmVersion);
        });
        return $this->pug;
    }
}