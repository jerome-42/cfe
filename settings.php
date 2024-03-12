<?php

class Settings {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function get($what, $defaultValue = null) {
        $q = "SELECT * FROM settings WHERE what = :what";
        $sth = $this->conn->prepare($q);
        $sth->execute([ ':what' => $what ]);
        if ($sth->rowCount() === 0)
            return $defaultValue;
        return $sth->fetchAll(PDO::FETCH_ASSOC)[0]['value'];
    }

    public function set($what, $value) {
        $q = "INSERT INTO settings (what, value) VALUES (:what, :value) ON DUPLICATE KEY UPDATE value = :value";
        $sth = $this->conn->prepare($q);
        $sth->execute([
            ':what' => $what,
            ':value' => $value,
        ]);
    }
}
