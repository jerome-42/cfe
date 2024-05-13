<?php

class Gliders {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function add($immat, $concours, $type, $aircraftType) {
        $q = "INSERT INTO glider (immat, concours, type, aircraftType) VALUES (:immat, :concours, :type, :aircraftType) ON DUPLICATE KEY UPDATE id = id";
        // on pourrait utiliser INSERT IGNORE INTO glider mais ça ne remonterait pas toutes les erreurs
        $sth = $this->conn->prepare($q);
        $sth->execute([ ':immat' => $immat, ':concours' => $concours, ':type' => $type, ':aircraftType' => $aircraftType ]);
    }

    public function editComment($id, $comment, $details) {
        $detailsLoaded = $this->getGliderById($id);
        if ($detailsLoaded['comment'] === $comment)
            return;

        if ($comment != '') {
            $q = "UPDATE glider SET comment = :comment, commentDetails = :details WHERE id = :id";
            $sth = $this->conn->prepare($q);
            $sth->execute([ ':comment' => $comment, ':details' => $details, ':id' => $id ]);
        } else {
            $q = "UPDATE glider SET comment = NULL, commentDetails = NULL WHERE id = :id";
            $sth = $this->conn->prepare($q);
            $sth->execute([ ':id' => $id ]);
        }
    }

    public function list($onlyVisible = false) {
        $q = "SELECT *, UNIX_TIMESTAMP(`cenExpirationDate`) AS `cenExpirationDate`, UNIX_TIMESTAMP(`aprsExpirationDate`) AS `aprsExpirationDate` FROM glider";
        if ($onlyVisible === true)
            $q .= " WHERE visible = 1";
        $q .= " ORDER BY immat";
        $sth = $this->conn->prepare($q);
        $sth->execute();
        $gliders = $sth->fetchAll(PDO::FETCH_ASSOC);
        foreach ($gliders as &$glider) {
            $flarm = $this->getLastFlarmLog($glider['id']);
            if ($flarm !== null)
                $glider = array_merge($glider, $flarm);
        }
        return $gliders;
    }

    public function listWithOGNAndFlarmnetStatus($onlyVisible = false) {
        $gliders = $this->list($onlyVisible);
        $ogn = new OGN();
        $flarmnet = new Flarmnet();
        foreach ($gliders as &$glider) {
            if (isset($glider['radioId'])) {
                $glider['ognStatus'] = $ogn->doesGliderIsRegistered($glider['immat'], $glider['radioId']);
                $glider['flarmnetStatus'] = $flarmnet->doesGliderIsRegistered($glider['immat'], $glider['radioId']);
            }
        }
        return $gliders;
    }

    public function getLastFlarmLog($gliderId, $fetchCompleteDeclaration = true) {
        $q = "SELECT *, UNIX_TIMESTAMP(`when`) AS `when` FROM flarm_logs WHERE glider = :id ORDER BY `when` DESC LIMIT 1";
        $sth = $this->conn->prepare($q);
        $sth->execute([ ':id' => $gliderId ]);
        if ($sth->rowCount() === 1) {
            $line = $sth->fetchAll(PDO::FETCH_ASSOC)[0];
            // si versionHard est NULL et who est OGN alors on essaye de compléter avec
            // le dernier fichier fichier que l'on a analysé
            $ognPersonne = Personne::loadOGN($this->conn); // OGN
            if ($fetchCompleteDeclaration === true && $line['versionHard'] === null && $line['who'] == $ognPersonne['id']) {
                $lastFileData = $this->getLastFlarmLogFromFile($gliderId);
                if ($lastFileData != null) {
                    // on a analysé un fichier igc, on remplace les données NULL d'OGN par celles-ci
                    foreach ([ 'versionHard', 'stealth', 'noTrack', 'rangeDetails', 'rangeBelowMinimum', 'flarmResultUrl', 'flarmAircraftType' ] as $key) {
                        $line[$key] = $lastFileData[$key];
                    }
                }
            }
            return [
                'flarmLogId' => $line['id'],
                'versionSoft' => $line['versionSoft'],
                'versionHard' => $line['versionHard'],
                'when' => $line['when'],
                'who' => $line['who'],
                'stealth' => $line['stealth'],
                'noTrack' => $line['noTrack'],
                'radioId' => $line['radioId'],
                'rangeDetails' => $line['rangeDetails'],
                'rangeBelowMinimum' => $line['rangeBelowMinimum'],
                'flarmResultUrl' => $line['flarmResultUrl'],
                'flarmAircraftType' => $line['aircraftType'],
            ];
        }
        return null;
    }

    private function getRadioId($gliderId) {
        $q = "SELECT radioId FROM flarm_logs WHERE glider = :id AND radioId IS NOT NULL and radioId != '' ORDER BY `when` DESC LIMIT 1";
        $sth = $this->conn->prepare($q);
        $sth->execute([ ':id' => $gliderId ]);
        if ($sth->rowCount() === 1) {
            $line = $sth->fetchAll(PDO::FETCH_ASSOC)[0];
            return $line['radioId'];
        }
        return null;
    }

    private function getLastFlarmLogFromFile($gliderId) {
        $q = "SELECT *, UNIX_TIMESTAMP(`when`) AS `when` FROM flarm_logs WHERE glider = :id AND versionHard IS NOT NULL ORDER BY `when` DESC LIMIT 1";
        $sth = $this->conn->prepare($q);
        $sth->execute([ ':id' => $gliderId ]);
        if ($sth->rowCount() === 1) {
            $line = $sth->fetchAll(PDO::FETCH_ASSOC)[0];
            return [ 'versionSoft' => $line['versionSoft'],
                     'versionHard' => $line['versionHard'],
                     'when' => $line['when'],
                     'who' => $line['who'],
                     'stealth' => $line['stealth'],
                     'noTrack' => $line['noTrack'],
                     'radioId' => $this->getRadioId($gliderId),
                     'rangeDetails' => $line['rangeDetails'],
                     'rangeBelowMinimum' => $line['rangeBelowMinimum'],
                     'flarmResultUrl' => $line['flarmResultUrl'],
                     'flarmAircraftType' => $line['aircraftType'],
            ];
        }
        return null;
    }

    public function getGliderById($id) {
        $q = "SELECT *, UNIX_TIMESTAMP(`cenExpirationDate`) AS `cenExpirationDate`, UNIX_TIMESTAMP(`aprsExpirationDate`) AS `aprsExpirationDate` FROM glider WHERE id = :id";
        $sth = $this->conn->prepare($q);
        $sth->execute([ ':id' => $id ]);
        if ($sth->rowCount() !== 1)
            return null;
        return $sth->fetchAll(PDO::FETCH_ASSOC)[0];
    }

    public function getGliderByImmat($immat) {
        $q = "SELECT *, UNIX_TIMESTAMP(`cenExpirationDate`) AS `cenExpirationDate`, UNIX_TIMESTAMP(`aprsExpirationDate`) AS `aprsExpirationDate` FROM glider WHERE immat = :immat";
        $sth = $this->conn->prepare($q);
        $sth->execute([ ':immat' => $immat ]);
        if ($sth->rowCount() !== 1)
            return null;
        return $sth->fetchAll(PDO::FETCH_ASSOC)[0];
    }

    public function registerFlarmLog($data) {
        $q = "INSERT INTO flarm_logs (glider, `when`, filename, versionSoft, versionHard, stealth, noTrack, radioId, rangeAvg, rangeDetails, rangeBelowMinimum, aircraftType, flarmResultUrl, who) VALUES (:glider, FROM_UNIXTIME(:when), :filename, :versionSoft, :versionHard, :stealth, :noTrack, :radioId, :rangeAvg, :rangeDetails, :rangeBelowMinimum, :aircraftType, :flarmResultUrl, :who) ON DUPLICATE KEY UPDATE versionSoft = :versionSoft, versionHard = :versionHard, who = :who, stealth = :stealth, noTrack = :noTrack, radioId = :radioId, rangeBelowMinimum = :rangeBelowMinimum, rangeAvg = :rangeAvg, rangeDetails = :rangeDetails, aircraftType = :aircraftType, flarmResultUrl = :flarmResultUrl";
        $sth = $this->conn->prepare($q);
        $sth->execute($data);
    }

    public function updateDataFromOSRT($osrt, $mysql, $forceUpdate = false) {
        foreach ($osrt['credentials'] as $credential) {
            $osrt = new OSRT($mysql);
            $osrt->updateGliderDetails($credential['login'], $credential['password'], $forceUpdate);
        }
    }
}
