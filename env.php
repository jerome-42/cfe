<?php

include_once __DIR__ . '/cache.php';
include_once __DIR__ . '/db.php';
include_once __DIR__ . '/flarm.php';
include_once __DIR__ . '/flarmnet.php';
include_once __DIR__ . '/gliders.php';
include_once __DIR__ . '/ogn.php';
include_once __DIR__ . '/osrt.php';
include_once __DIR__ . '/settings.php';

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
        $this->pug->share('isBefore', function($t1, $t2) {
            return intval($t1) < intval($t2);
        });
        $this->pug->share('timestampToDate', function($text) {
            if (is_numeric($text) === false)
                return 'NA';
            return date('d/m/Y', intval($text));
        });
        $this->pug->share('isAGoodFlarmVersion', function($version) {
            $settings = new Settings($this->mysql);
            $goodFlarmVersion = explode("\n", $settings->get('flarmGoodSoftVersion', ''));
            $goodFlarmVersion = array_map('trim', $goodFlarmVersion);
            return in_array($version, $goodFlarmVersion);
        });
        $this->pug->share('my_substr', function($text, $nbChar) {
            if (is_numeric($nbChar) === false)
                return $text;
            $nbChar = intval($nbChar);
            if (strlen($text) <= $nbChar)
                return $text;
            return substr($text, 0, $nbChar).'...';
        });
        $this->pug->share('tinyintVersText', function($text) {
            if (intval($text) === 1)
                return 'oui';
            return 'non';
        });
        $this->pug->share('timeago', function($text, $prefix = null) {
            if (!is_numeric($text))
                return $text;
            $timestamp = intval($text);

            $strTime = array("seconde", "minute", "heure", "jour", "mois", "an");
            $length = array("60","60","24","30","12","10");

            $currentTime = time();
            if ($currentTime >= $timestamp) {
                $diff = time() - $timestamp;
                for ($i = 0; $diff >= $length[$i] && $i < count($length) - 1; $i++) {
                    $diff = $diff / $length[$i];
                }

                $diff = round($diff);
                $toRet = "il y a ".$diff." ".pluralize($diff, $strTime[$i]);
                if ($prefix !== null)
                    $toRet = $prefix . ' ' . $toRet;
                return $toRet;
            }
            return '';
        });
        $this->pug->share('reverseTimeago', function($text, $prefix = null) {
            if (!is_numeric($text))
                return $text;
            $timestamp = intval($text);

            $strTime = array("seconde", "minute", "heure", "jour", "mois", "an");
            $length = array("60","60","24","30","12","10");

            $currentTime = time();
            if ($currentTime <= $timestamp) {
                $diff = $timestamp - time();
                for ($i = 0; $diff >= $length[$i] && $i < count($length) - 1; $i++) {
                    $diff = $diff / $length[$i];
                }

                $diff = round($diff);
                $toRet = "dans ".$diff." ".pluralize($diff, $strTime[$i]);
                if ($prefix !== null)
                    $toRet = $prefix . ' ' . $toRet;
                return $toRet;
            }
            return '';
        });
        return $this->pug;
    }
}
