<?php

class CFE {
    private $givavNumber;
    private $conn;

    public function __construct($conn, $givavNumber) {
        $this->conn = $conn;
        $this->givavNumber = $givavNumber;
    }

    private function getLines($validation) {
        $query = 'SELECT COALESCE(SUM(duration), 0) as total FROM cfe_records WHERE who = :givavNumber AND status = :statut'; // TODO WHERE année
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':givavNumber' => $this->givavNumber, ':statut' => $validation ]);
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

    public function getRecords() {
        $query = 'SELECT * FROM cfe_records WHERE who = :givavNumber ORDER BY workDate DESC'; // TODO WHERE année
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':givavNumber' => $this->givavNumber ]);
        $lines = $sth->fetchAll();
        return $lines;
    }

    private function getCFE_TODO() {
        $query = "SELECT COALESCE(cfeTODO, settings.value) AS cfeTODO FROM personnes JOIN settings ON settings.what = 'defaultCFE_TODO' WHERE givavNumber = :num";
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':num' => $this->givavNumber ]);
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

    public function getStats() {
        return [ 'submited' => floatval($this->getLines('submitted')),
                 'validated' => floatval($this->getLines('validated')),
                 'rejected' => floatval($this->getLines('rejected')),
                 'thecfetodo' => floatval($this->getCFE_TODO())	];
    }
}
