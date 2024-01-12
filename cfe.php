<?php

class CFE {
    private $givavNumber;
    private $conn;

    public function __construct($conn, $givavNumber) {
        $this->conn = $conn;
        $this->givavNumber = $givavNumber;
    }

    private function getLines($validation) {
        $query = 'SELECT COALESCE(SUM(durée), 0) as total FROM cfe_records WHERE NumNational = :givavNumber AND Validation = :statut';
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':givavNumber' => $this->givavNumber, ':statut' => $validation ]);
        $lines = $sth->fetchAll();
        return $lines[0]['total'];
    }

    public function getStats() {
        return [ 'submited' => $this->getLines('Soumis'),
                 'validated' => $this->getLines('Validé'),
                 'rejected' => $this->getLines('Rejeté') ];
    }
}
