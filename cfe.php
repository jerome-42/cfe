<?php

class CFE {
    private $conn;
    private $defaultCFE_TODO = null;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getLine($id) {
        $query = 'SELECT * FROM cfe_records WHERE id = :id';
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':id' => $id ]);
        if ($sth->rowCount() !== 1)
            return null;
        $lines = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $lines[0];
    }

    public function getLastRecords() {
        $query = 'SELECT cfe_records.*, personnes.name, personnes.givavNumber, validated.name as validatedName,
cfe_proposals.id AS `proposalId`,
cfe_proposals.title AS `proposalTitle`
FROM cfe_records
JOIN personnes ON personnes.givavNumber = cfe_records.who
LEFT JOIN personnes validated ON validated.givavNumber = cfe_records.statusWho
LEFT JOIN cfe_proposals ON cfe_proposals.id = cfe_records.proposal
WHERE YEAR(workDate) = YEAR(NOW())
ORDER BY workDate DESC LIMIT 200';
        $sth = $this->conn->prepare($query);
        $sth->execute([ ]);
        $lines = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $lines;
    }

    private function getLines($status, $givavNumber, $year) {
        $query = 'SELECT COALESCE(SUM(duration), 0) as total FROM cfe_records WHERE who = :givavNumber AND status = :status AND YEAR(workDate) = :year';
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':givavNumber' => $givavNumber, ':status' => $status,
                        ':year' => $year ]);
        $lines = $sth->fetchAll();
        return $lines[0]['total'];
    }

    private function getLines2($status, $givavNumber, $year, $cond = null) {
        $query = 'SELECT COALESCE(SUM(duration), 0) as total FROM cfe_records WHERE who = :givavNumber AND status = :status AND YEAR(workDate) = :year';
        if ($cond != null)
            $query .= $cond;
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':givavNumber' => $givavNumber, ':status' => $status,
                        ':year' => $year ]);
        $lines = $sth->fetchAll();
        return $lines[0]['total'];
    }

    public function getLinesToValidate() {
        $query = "SELECT cfe_records.*, personnes.name, personnes.givavNumber, cfe_proposals.title,
cfe_proposals.id AS `proposalId`,
cfe_proposals.title AS `proposalTitle`
FROM cfe_records
JOIN personnes ON cfe_records.who = personnes.givavNumber
LEFT JOIN cfe_proposals ON cfe_proposals.id = cfe_records.proposal
WHERE cfe_records.status = 'submitted' ORDER BY cfe_records.workDate ASC";
        $sth = $this->conn->prepare($query);
        $sth->execute([]);
        $lines = $sth->fetchAll();
        return $lines;
    }

    public function getAllRecords($year) {
        $query = 'SELECT * FROM cfe_records WHERE YEAR(workDate) = :year ORDER BY workDate DESC';
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':year' => $year ]);
        $lines = $sth->fetchAll();
        return $lines;
    }

    public function getRecords($givavNumber) {
        $query = 'SELECT *, YEAR(workDate) AS year FROM cfe_records WHERE who = :givavNumber ORDER BY workDate DESC';
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':givavNumber' => $givavNumber ]);
        $lines = $sth->fetchAll();
        return $lines;
    }

    public function getRecordsByYear($givavNumber, $year) {
        $query = 'SELECT *, validated.name as validatedName FROM cfe_records LEFT JOIN personnes validated ON validated.givavNumber = cfe_records.statusWho WHERE who = :givavNumber AND YEAR(workDate) = :year ORDER BY workDate DESC';
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':year' => $year, ':givavNumber' => $givavNumber ]);
        $lines = $sth->fetchAll();
        return $lines;
    }

    private function getCFE_TODO($givavNumber, $year) {
        if (!is_numeric($year))
            throw new Exception("l'année doit être un nombre");
        $query = "SELECT todo FROM cfe_todo WHERE who = :who AND year = :year";
        $sth = $this->conn->prepare($query);
        $sth->execute([ 'year' => $year, ':who' => $givavNumber ]);
        if ($sth->rowCount() === 1)
            return $sth->fetchAll()[0]['todo'];
        // pas de ligne dans cfe_todo, donc on prend la ligne par défaut dans settings
        $query = "SELECT value FROM settings WHERE settings.what = :what";
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':what' => 'defaultCFE_TODO_'.$year ]);
        if ($sth->rowCount() !== 1)
            throw new Exception("pas de ligne concernant le nombre d'heure par défaut dans settings pour l'année ".$year);
        $lines = $sth->fetchAll();
        return intval($lines[0]['value']);
    }

    public function getDefaultCFE_TODO($year) {
        $query = "SELECT value FROM settings WHERE what = :what";
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':what' => 'defaultCFE_TODO_'.$year ]);
        if ($sth->rowCount() !== 1)
            throw new Exception("pas de settings defaultCPE_TODO pour l'année ".$year);
        $lines = $sth->fetchAll();
        return intval($lines[0]['value']);
    }

    public function getSubmittedDuration() {
        $query = "SELECT SUM(duration) AS duration FROM cfe_records WHERE YEAR(workDate) = YEAR(NOW()) AND status = 'submitted'";
        $sth = $this->conn->prepare($query);
        $sth->execute([ ]);
        if ($sth->rowCount() !== 1)
            return null;
        $lines = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $lines[0]['duration'];
    }

    public function isCompleted($membre) {
        if ($membre['cfeValidated'] >= $membre['cfeTODO'])
            return 1;
        else
            return 0;
    }

    public function getStats($givavNumber, $year) {
        $data = [ 'submited' => floatval($this->getLines('submitted', $givavNumber, $year)),
                  'rejected' => floatval($this->getLines('rejected', $givavNumber, $year)),
                  'thecfetodo' => floatval($this->getCFE_TODO($givavNumber, $year)),
                  'private' => 0 ];

        $personne = new Personne($this->conn);
        // si pas propriétaire alors on supprime toutes les heures réalisés pour les privés
        if ($personne->load($this->conn, $givavNumber)['isOwnerOfGlider'] === 0) {
            $data['validated'] = floatval($this->getLines2('validated', $givavNumber, $year, " AND beneficiary = 'AAVO'"));
            $data['private'] = floatval($this->getLines2('validated', $givavNumber, $year, " AND beneficiary != 'AAVO'"));
        }
        else {
            // c'est un propriétaire, on ne garde que maxi 16h de privé
            $nbHoursPrive = $this->getLines2('validated', $givavNumber, $year, " AND beneficiary != 'AAVO'");
            $nbHoursAAVO = $this->getLines2('validated', $givavNumber, $year, " AND beneficiary = 'AAVO'");
            if ($nbHoursPrive < 16*60) {
                $data['validated'] = $nbHoursAAVO + $nbHoursPrive;
                $data['private'] = 0;
            }
            else {
                $data['validated'] = $nbHoursAAVO + 16*60;
                $data['private'] = $nbHoursPrive - 16*60;
            }
        }

        if ($data['validated'] >= $data['thecfetodo'])
            $data['completed'] = true;
        else
            $data['completed'] = false;
        return $data;
    }

    public function getValidated($givavNumber, $year) {
        return $this->getStats($givavNumber, $year)['validated'];
    }

    public function getLinesOfProposal($proposalId) {
        $query = 'SELECT cfe_records.*, personnes.name, personnes.givavNumber, validated.name as validatedName, YEAR(workDate) AS year
FROM cfe_records
JOIN personnes ON personnes.givavNumber = cfe_records.who
LEFT JOIN personnes validated ON validated.givavNumber = cfe_records.statusWho
WHERE cfe_records.proposal = :id
ORDER BY workDate DESC';
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':id' => $proposalId ]);
        $lines = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $lines;
    }
}
