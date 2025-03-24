<?php

class Devis {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function addFile($quotationId, $filename, $size, $mime, $data) {
        $query = "INSERT INTO quotation_files (quotation_id, filename, size, mime, data) VALUES (:quotation_id, :filename, :size, :mime, :data)";
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':quotation_id' => $quotationId, ':filename' => $filename, ':size' => $size, ':mime' => $mime, ':data' => $data ]);
        return $this->conn->lastInsertId();
    }

    public function create($details, $givavNumber) {
        $query = "INSERT INTO quotation (who, `when`, details, status) VALUES (:who, NOW(), :details, 'submitted')";
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':who' => $givavNumber, ':details' => $details ]);
        return $this->conn->lastInsertId();
    }

    public function delete($id) {
        $query = "DELETE FROM quotation WHERE id = :id";
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':id' => $id ]);
        $query = "DELETE FROM quotation_files WHERE quotation_id = :id";
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':id' => $id ]);
    }

    public function get($id) {
        $query = "SELECT *, UNIX_TIMESTAMP(`when`) AS `when` FROM quotation WHERE id = :id";
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':id' => $id ]);
        if ($sth->rowCount() !== 1)
            throw new Exception("La quotation ".$id." n'existe pas");
        $quote = $sth->fetchAll()[0];
        $query = "SELECT id, filename, size, mime FROM quotation_files WHERE quotation_id = :id";
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':id' => $id ]);
        $quote['files'] = $sth->fetchAll();
        return $quote;
    }

    public function getFile($id) {
        $query = "SELECT id, filename, size, mime, quotation_id FROM quotation_files WHERE id = :id";
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':id' => $id ]);
        if ($sth->rowCount() != 1)
            throw new Exception("le fichier n'existe pas");
        return $sth->fetchAll()[0];
    }

    public function getFileData($id) {
        $query = "SELECT data FROM quotation_files WHERE id = :id";
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':id' => $id ]);
        return $sth->fetchAll()[0]['data'];
    }

    public function listAll() {
        $query = "SELECT quotation.*, UNIX_TIMESTAMP(`when`) AS `when`, personnes.name FROM quotation
JOIN personnes ON personnes.givavNumber = quotation.who ORDER BY `when` DESC";
        $sth = $this->conn->prepare($query);
        $sth->execute();
        return $sth->fetchAll();
    }

    public function listMine($givavNumber) {
        $query = "SELECT *, UNIX_TIMESTAMP(`when`) AS `when` FROM quotation WHERE who = :who ORDER BY `when` DESC";
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':who' => $givavNumber ]);
        return $sth->fetchAll();
    }

    public function updateStatus($id, $status) {
        $query = "UPDATE quotation SET status = :status WHERE id = :id";
        $sth = $this->conn->prepare($query);
        $sth->execute([ ':id' => $id, ':status' => $status ]);
    }
}
