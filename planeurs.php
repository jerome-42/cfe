<?php

class Planeurs {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function liste($onlyVisible = false) {
        $q = "SELECT * FROM planeurs ORDER BY immat";
        if ($onlyVisible === true)
            $q = "SELECT * FROM planeurs WHERE visible = 1 ORDER BY immat";
        $sth = $this->conn->prepare($q);
        $sth->execute();
        $machines = $sth->fetchAll(PDO::FETCH_ASSOC);
        foreach ($machines as &$machine) {
            $flarm = $this->getDerniereDeclarationFlarm($machine['id']);
            $machine = array_merge($machine, $flarm);
        }
        return $machines;
    }

    public function getDerniereDeclarationFlarm($machineId) {
        $q = "SELECT *, UNIX_TIMESTAMP(quand) AS quand FROM flarm_logs WHERE planeur = :id ORDER BY quand DESC LIMIT 1";
        $sth = $this->conn->prepare($q);
        $sth->execute([ ':id' => $machineId ]);
        if ($sth->rowCount() === 1) {
            $line = $sth->fetchAll(PDO::FETCH_ASSOC)[0];
            return [ 'version_soft' => $line['version_soft'],
                     'version_hard' => $line['version_hard'],
                     'quand' => $line['quand'],
                     'flarm_mis_a_jour_par' => $line['who'],
                     'stealth' => $line['stealth'],
                     'noTrack' => $line['noTrack'],
                     'radioId' => $line['radioId'],
                     'porteeDetails' => $line['porteeDetails'],
                     'porteeEnDecaDuMinimum' => $line['porteeEnDecaDuMinimum'],
            ];
        }
        return [ 'version_soft' => 'NA', 'version_hard' => 'NA', 'quand' => 'NA', 'flarm_mis_a_jour_par' => '' ];
    }

    public function getPlaneurDepuisId($id) {
        $q = "SELECT * FROM planeurs WHERE id = :id";
        $sth = $this->conn->prepare($q);
        $sth->execute([ ':id' => $id ]);
        if ($sth->rowCount() !== 1)
            return null;
        return $sth->fetchAll(PDO::FETCH_ASSOC)[0];
    }

    public function getPlaneurDepuisImmat($immat) {
        $q = "SELECT * FROM planeurs WHERE immat = :immat";
        $sth = $this->conn->prepare($q);
        $sth->execute([ ':immat' => $immat ]);
        if ($sth->rowCount() !== 1)
            return null;
        return $sth->fetchAll(PDO::FETCH_ASSOC)[0];
    }

    public function enregistreFlarm($data) {
        $q = "INSERT INTO flarm_logs (planeur, quand, fichier, version_soft, version_hard, who, stealth, noTrack, radioId, porteeEnDecaDuMinimum, porteeMoyenne, porteeDetails) VALUES (:planeur, FROM_UNIXTIME(:quand), :fichier, :version_soft, :version_hard, :who, :stealth, :noTrack, :radioId, :porteeEnDecaDuMinimum, :porteeMoyenne, :porteeDetails) ON DUPLICATE KEY UPDATE version_soft = :version_soft, version_hard = :version_hard, who = :who, stealth = :stealth, noTrack = :noTrack, radioId = :radioId, porteeEnDecaDuMinimum = :porteeEnDecaDuMinimum, porteeMoyenne = :porteeMoyenne, porteeDetails = :porteeDetails";
        $sth = $this->conn->prepare($q);
        $sth->execute($data);
    }

    public function getFlarmLogs($planeurId) {
        $q = "SELECT *, UNIX_TIMESTAMP(quand) AS quand FROM flarm_logs WHERE planeur = :id ORDER BY quand DESC";
        $sth = $this->conn->prepare($q);
        $sth->execute([ ':id' => $planeurId ]);
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }
}
