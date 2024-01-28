<?php

class Personne {
    static public function creeOuMAJ($conn, $user) {
        $query = "INSERT INTO personnes (name, Email, NumNational) VALUES (:name, :email, :numNational) ON DUPLICATE KEY UPDATE name = :name, email = :email";
        $sth = $conn->prepare($query);
        $sth->execute([ ':name' => $user['name'], ':email' => $user['mail'], ':numNational' => $user['number'] ]);
        $conn->commit();
    }

    static public function estAdmin($conn, $numGivav) {
        $query = "SELECT 1 FROM personnes WHERE NumNational = :num AND estAdmin IS true";
        $sth = $conn->prepare($query);
        $sth->execute([ ':num' => $numGivav ]);
        return $sth->rowCount() === 1;
    }
}
