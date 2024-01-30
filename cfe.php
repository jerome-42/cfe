<?php

class CFE {
    private $conn;
    private $defaultCFE_TODO = null;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    private function getLines($validation, $givavNumber) {
        $query = 'SELECT COALESCE(SUM(duration), 0) as total FROM cfe_records WHERE who = :givavNumber AND status = :statut'; // TODO WHERE année
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':givavNumber' => $givavNumber, ':statut' => $validation ]);
        $lines = $sth->fetchAll();
        return $lines[0]['total'];
    }

    public function getLinesToValidate() {
        $query = "SELECT cfe_records.*, personnes.name FROM cfe_records JOIN personnes ON cfe_records.who = personnes.givavNumber WHERE cfe_records.status = 'submitted' ORDER BY cfe_records.workDate ASC";
        $sth = $this->conn->prepare($query);
        $sth->execute([]);
        $lines = $sth->fetchAll();
        return $lines;
    }

    public function getAllRecords() {
        $query = 'SELECT * FROM cfe_records ORDER BY workDate DESC';
        $sth = $this->conn->prepare($query);
        $sth->execute([]);
        $lines = $sth->fetchAll();
        return $lines;
    }

    public function getRecords($givavNumber) {
        $query = 'SELECT * FROM cfe_records WHERE who = :givavNumber ORDER BY workDate DESC'; // TODO WHERE année
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':givavNumber' => $givavNumber ]);
        $lines = $sth->fetchAll();
        return $lines;
    }

    private function getCFE_TODO($givavNumber) {
        $query = "SELECT COALESCE(cfeTODO, settings.value) AS cfeTODO FROM personnes JOIN settings ON settings.what = 'defaultCFE_TODO' WHERE givavNumber = :num";
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':num' => $givavNumber ]);
        if ($sth->rowCount() !== 1)
            throw new Exception("pas de ligne dans personnes pour cet utilisateur");
        $lines = $sth->fetchAll();
        return $lines[0]['cfeTODO'];
    }

    public function getDefaultCFE_TODO() {
        $query = "SELECT value FROM settings WHERE what = 'defaultCFE_TODO'";
        $sth = $this->conn->prepare($query);
        $sth->execute([]);
        if ($sth->rowCount() !== 1)
            throw new Exception("pas de settings defaultCPE_TODO");
        $lines = $sth->fetchAll();
        return $lines[0]['value'];
    }

    public function isCompleted($membre) {
        if ($membre['cfeValidated'] >= $membre['cfeTODO'])
            return 1;
        else
            return 0;
    }

    public function getStats($givavNumber) {
        $data = [ 'submited' => floatval($this->getLines('submitted', $givavNumber)),
                 'validated' => floatval($this->getLines('validated', $givavNumber)),
                 'rejected' => floatval($this->getLines('rejected', $givavNumber)),
                 'thecfetodo' => floatval($this->getCFE_TODO($givavNumber)) ];

        if ($data['validated'] >= $data['thecfetodo'])
            $data['completed'] = true;
        else
            $data['completed'] = false;
        return $data;
    }

    public function getValidated($givavNumber) {
        return floatval($this->getLines('validated', $givavNumber));
    }
}
