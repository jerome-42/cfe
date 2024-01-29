<?php

class CFE {
    private $givavNumber;
    private $conn;

    public function __construct($conn, $givavNumber) {
        $this->conn = $conn;
        $this->givavNumber = $givavNumber;
    }

    private function getLines($validation) {
        $query = 'SELECT COALESCE(SUM(Durée), 0) as total FROM cfe_records WHERE NumNational = :givavNumber AND Validation = :statut'; // TODO WHERE année
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':givavNumber' => $this->givavNumber, ':statut' => $validation ]);
        $lines = $sth->fetchAll();
        return $lines[0]['total'];
    }

    public function getRecords() {
        $query = 'SELECT * FROM cfe_records WHERE NumNational = :givavNumber ORDER BY DateTravaux DESC'; // TODO WHERE année
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':givavNumber' => $this->givavNumber ]);
        $lines = $sth->fetchAll();
        return $lines;
    }

    private function getTask($cfetodo) {
        $query = 'SELECT COALESCE(cfetodo, 0) AS cfetodo FROM personnes WHERE NumNational = :givavNumber';
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':givavNumber' => $this->givavNumber ]);
        if ($sth->rowCount() !== 1)
            throw new Exception("pas de ligne dans personnes pour cet utilisateur");
        $lines = $sth->fetchAll();
        return $lines[0]['cfetodo'];
    }


    public function getStats() {
        return [ 'submited' => floatval($this->getLines('Soumis')),
                 'validated' => floatval($this->getLines('Validé')),
                 'rejected' => floatval($this->getLines('Rejeté')),
                 'thecfetodo' => floatval($this->getTask('cfetodo'))	];
    }
}
