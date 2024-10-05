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
        $query = 'SELECT *, cfe_records.id, validated.name as validatedName FROM cfe_records LEFT JOIN personnes validated ON validated.givavNumber = cfe_records.statusWho WHERE who = :givavNumber AND YEAR(workDate) = :year ORDER BY workDate DESC';
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

    public function getVA($givavNumber, $year) {
        if (!is_numeric($year))
            throw new Exception("l'année doit être un nombre");
        $query = "SELECT minutes FROM va WHERE who = :who AND year = :year";
        $sth = $this->conn->prepare($query);
        $sth->execute([ 'year' => $year, ':who' => $givavNumber ]);
        if ($sth->rowCount() === 1)
            return $sth->fetchAll()[0]['minutes'];
        return null;
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
                  'validated' => floatval($this->getLines2('validated', $givavNumber, $year)),
                  'vaValidated' => 0 ]; // va en exces et non comptabilisé

        $personne = new Personne($this->conn);
        if ($personne->load($this->conn, $givavNumber)['isOwnerOfGlider'] === 1) {
            // c'est un propriétaire, on ne garde que maxi 16h de VA
            $nbHoursVA = $this->getLines2('validated', $givavNumber, $year, " AND beneficiary = 'VA'");
            $nbHoursOthers = $this->getLines2('validated', $givavNumber, $year, " AND beneficiary != 'VA'");
            $va = $this->getVA($givavNumber, $year);
            if ($va != null) {
                if ($nbHoursVA <= $va) {
                    $data['validated'] = $nbHoursOthers + $nbHoursVA;
                    $data['vaValidated'] = $nbHoursVA;
                }
                else {
                    $data['validated'] = $nbHoursOthers + $va;
                    $data['vaValidated'] = $nbHoursVA - $va;
                }
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

    private function getFirstCFERecords() {
        $q = "SELECT EXTRACT(YEAR FROM registerDate) AS year FROM cfe_records";
        $sth = $this->conn->prepare($q);
        $sth->execute();
        $lines = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $lines[0]['year'];
    }

    public function getStatsTableauDeBord($year) {
        $stats = [
            'cfe' => [
                'annee' => date('Y'),
                'declarationsCFE' => [],
                'declarationsCFE_n_anneesPrecedantes' => [],
            ],
        ];
        $cumulDeclarationCFE = 0;
        for ($i = 1; $i <= date('n'); $i++) {
            $q = "SELECT COALESCE(ROUND(SUM(duration)/60), 0) AS nb FROM cfe_records WHERE status = 'validated' AND EXTRACT(YEAR FROM registerDate) = :year AND EXTRACT(MONTH FROM registerDate) = :month";
            $sth = $this->conn->prepare($q);
            $sth->execute([ ':year' => date('Y'), ':month' => $i ]);
            $cumulDeclarationCFE = $cumulDeclarationCFE + $sth->fetchAll(PDO::FETCH_ASSOC)[0]['nb'];
            $stats['cfe']['declarationsCFE'][] = $cumulDeclarationCFE;
        }
        $cumulDeclarationCFE_n_anneesPrecedantes = 0;
        // on cherche la première déclaration pour connaître le nombre d'année de l'historique
        $firstYear = $this->getFirstCFERecords();
        $nbYearBetweenFirstAndCurrentYear = date('Y') - $firstYear;
        if ($nbYearBetweenFirstAndCurrentYear > 5) {
            $firstYear = date('Y') - 5;
            $nbYearBetweenFirstAndCurrentYear = 5;
        }
        $stats['cfe']['moyenne_sur_nb_annee'] = $nbYearBetweenFirstAndCurrentYear;
        for ($i = 1; $i <= 12; $i++) {
            $q = "SELECT COALESCE(ROUND(SUM(duration)/60), 0)/:nbAnnees AS nb FROM cfe_records WHERE status = 'validated' AND EXTRACT(YEAR FROM registerDate) >= :firstYear AND EXTRACT(YEAR FROM registerDate) <= :lastYear AND EXTRACT(MONTH FROM registerDate) = :month";
            $sth = $this->conn->prepare($q);
            $sth->execute([ ':firstYear' => $firstYear, ':lastYear' => date('Y')-1, ':month' => $i, ':nbAnnees' => $nbYearBetweenFirstAndCurrentYear ]);
            $cumulDeclarationCFE_n_anneesPrecedantes = $cumulDeclarationCFE_n_anneesPrecedantes + $sth->fetchAll(PDO::FETCH_ASSOC)[0]['nb'];
            $stats['cfe']['declarationsCFE_n_anneesPrecedantes'][] = $cumulDeclarationCFE_n_anneesPrecedantes;
        }
        return $stats;
    }

    public function switchToVA($id) {
        $q = "UPDATE cfe_records SET beneficiary = 'VA' WHERE id = :id";
        $sth = $this->conn->prepare($q);
        $sth->execute([ ':id' => $id ]);
    }
}
