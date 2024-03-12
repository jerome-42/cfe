<?php

class Gliders {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function list($onlyVisible = false) {
        $q = "SELECT * FROM glider ORDER BY immat";
        if ($onlyVisible === true)
            $q = "SELECT * FROM glider WHERE visible = 1 ORDER BY immat";
        $sth = $this->conn->prepare($q);
        $sth->execute();
        $gliders = $sth->fetchAll(PDO::FETCH_ASSOC);
        foreach ($gliders as &$glider) {
            $flarm = $this->getLastFlarmLog($glider['id']);
            $glider = array_merge($glider, $flarm);
        }
        return $gliders;
    }

    public function getLastFlarmLog($gliderId) {
        $q = "SELECT *, UNIX_TIMESTAMP(`when`) AS `when` FROM flarm_logs WHERE glider = :id ORDER BY `when` DESC LIMIT 1";
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
                     'radioId' => $line['radioId'],
                     'rangeDetails' => $line['rangeDetails'],
                     'rangeBelowMinimum' => $line['rangeBelowMinimum'],
                     'flarmResultUrl' => $line['flarmResultUrl'],
            ];
        }
        return [ 'versionSoft' => 'NA', 'versionHard' => 'NA', 'when' => 'NA', 'who' => '' ];
    }

    public function getGliderById($id) {
        $q = "SELECT * FROM glider WHERE id = :id";
        $sth = $this->conn->prepare($q);
        $sth->execute([ ':id' => $id ]);
        if ($sth->rowCount() !== 1)
            return null;
        return $sth->fetchAll(PDO::FETCH_ASSOC)[0];
    }

    public function getGliderByImmat($immat) {
        $q = "SELECT * FROM glider WHERE immat = :immat";
        $sth = $this->conn->prepare($q);
        $sth->execute([ ':immat' => $immat ]);
        if ($sth->rowCount() !== 1)
            return null;
        return $sth->fetchAll(PDO::FETCH_ASSOC)[0];
    }

    public function registerFlarmLog($data) {
        $q = "INSERT INTO flarm_logs (glider, `when`, filename, versionSoft, versionHard, stealth, noTrack, radioId, rangeAvg, rangeDetails, rangeBelowMinimum, flarmResultUrl, who) VALUES (:glider, FROM_UNIXTIME(:when), :filename, :versionSoft, :versionHard, :stealth, :noTrack, :radioId, :rangeAvg, :rangeDetails, :rangeBelowMinimum, :flarmResultUrl, :who) ON DUPLICATE KEY UPDATE versionSoft = :versionSoft, versionHard = :versionHard, who = :who, stealth = :stealth, noTrack = :noTrack, radioId = :radioId, rangeBelowMinimum = :rangeBelowMinimum, rangeAvg = :rangeAvg, rangeDetails = :rangeDetails, flarmResultUrl = :flarmResultUrl";
        $sth = $this->conn->prepare($q);
        $sth->execute($data);
    }

    public function getFlarmLogs($gliderId) {
        $q = "SELECT *, UNIX_TIMESTAMP(`when`) AS `when` FROM flarm_logs WHERE glider = :id ORDER BY `when` DESC";
        $sth = $this->conn->prepare($q);
        $sth->execute([ ':id' => $gliderId ]);
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }
}
