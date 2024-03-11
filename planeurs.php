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
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    
}
