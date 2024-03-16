<?php

class OGN {
    private $database;
    private $databaseFilename;

    public function __construct() {
        $this->cache = new Cache();
        $this->databaseFilename = 'ogn-database.json';
    }

    public function doesGliderIsRegistered($immat, $radioId) {
        if ($this->database === null)
            $this->fetchAndParseDatabase();
        $immatFound = false;
        $radioIdFound = false;
        $tracked = false;
        foreach ($this->database['devices'] as $device) {
            if ($device['registration'] === $immat)
                $immatFound = true;
            if ($device['device_id'] === $radioId) {
                $radioIdFound = true;
                $tracked = $device['tracked'] === 'Y' ? true : false;
            }
        }
        if ($immatFound === true && $radioIdFound === true && $tracked === true)
            return null;
        if ($immatFound === true && $radioIdFound === false)
            return "Déclaré sur OGN mais pas avec le bon id radio";
        if ($immatFound === false && $radioIdFound === true)
            return "Déclaré sur OGN mais pas avec la bonne immatriculation";
        if ($tracked === false)
            return "Déclaré sur OGN mais enregistré pour ne pas être suivi (tracked = N)";
        return "Non déclaré sur OGN";
    }

    private function fetchAndParseDatabase($force = false) {
        $data = $this->cache->getContentFromCacheAndDownloadIfNecessary($this->databaseFilename, 'https://ddb.glidernet.org//download/?j=1', 7, $force);
        $this->database = json_decode($data, true);
    }

    public function getDatabaseCreationDate() {
        return $this->cache->getCacheStatus($this->databaseFilename);
    }

    public function refreshDatabase() {
        $this->fetchAndParseDatabase(true);
    }
}
