<?php

class Flarmnet {
    private $database;
    private $databaseFilename;

    public function __construct() {
        $this->cache = new Cache();
        $this->databaseFilename = 'flarmnet-database.json';
    }

    public function doesGliderIsRegistered($immat, $radioId) {
        if ($this->database === null)
            $this->fetchAndParseDatabase();
        $immatFound = false;
        $radioIdFound = false;
        foreach ($this->database['devices'] as $device) {
            if ($device['immat'] === $immat)
                $immatFound = true;
            if ($device['radioId'] === $radioId) {
                $radioIdFound = true;
            }
        }
        if ($immatFound === true && $radioIdFound === true)
            return null;
        if ($immatFound === true && $radioIdFound === false)
            return "Déclaré sur Flarmnet mais pas avec le bon id radio";
        if ($radioIdFound === false &&  $radioIdFound === true)
            return "Déclaré sur Flarmnet mais pas avec la bonne immatriculation";
        return "Non déclaré sur Flarmnet";
    }

    private function fetchAndParseDatabase($force = false) {
        $data = $this->cache->getContentFromCacheAndDownloadIfNecessary($this->databaseFilename, 'https://www.flarmnet.org/static/files/wfn/data.fln', 7, $force, function($data) {
            $decode = function($text, $length) {
                $ret = "";
                for ($i = 0; $i < $length; $i = $i + 2) {
                    $char = substr($text, $i, 2);
                    $ret .= chr(hexdec($char));
                }
                return preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', trim($ret));
            };

            $database = [];
            $lineNo = 0;
            foreach(preg_split("/((\r?\n)|(\r\n?))/", $data) as $line) {
                if ($lineNo++ == 0)
                    continue;
                $database[] = [
                    'radioId' => $decode(substr($line, 0, 12), 12),
                    'immat' => $decode(substr($line, 138, 14), 14),
                ];
            }
            $data = json_encode([ 'devices' => $database ]);
            return $data;
        });
        $this->database = json_decode($data, true);
    }

    public function getDatabaseCreationDate() {
        return $this->cache->getCacheStatus($this->databaseFilename);
    }

    public function refreshDatabase() {
        $this->fetchAndParseDatabase(true);
    }
}
