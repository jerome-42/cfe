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
        foreach ($this->database['radioId'] as $line) {
            if ($line['registration'] == $immat)
                $immatFound = true;
        }
        if (isset($this->database['radioId'][$radioId])) {
            $radioIdFound = true;
            $device = $this->database['radioId'][$radioId];
            $tracked = $device['tracked'] === 'Y' ? true : false;
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

    public function getGliderImmatFromRadioId($radioId) {
        if ($this->database === null)
            $this->parseDatabase();
        if (isset($this->database['immat'][$radioId]))
            return $this->database['immat'][$radioId]['registration'];
        return null;
    }

    private function fetchAndParseDatabase($force = false) {
        $data = $this->cache->getContentFromCacheAndDownloadIfNecessary($this->databaseFilename, 'https://ddb.glidernet.org//download/?j=1', 7, $force, function($data) {
            $data = json_decode($data, true);
            $database = [ 'radioId' => [], 'immat' => [] ];
            foreach ($data['devices'] as $line) {
                $radioId = $line['device_id'];
                $database['radioId'][$radioId] = $line;
                $immat = $line['registration'];
                //$database['immat'][$immat] = $line; // plus de CPU et moins de RAM utilisé
            }
            return json_encode($database);
        });
        $this->database = json_decode($data, true);
    }

    private function parseDatabase() {
        $data = $this->cache->getContentFromCacheNoDownload($this->databaseFilename);
        $this->database = json_decode($data, true);
    }

    public function getDatabaseCreationDate() {
        return $this->cache->getCacheStatus($this->databaseFilename);
    }

    public function refreshDatabase() {
        $this->fetchAndParseDatabase(true);
    }
}
