<?php

class Personne {
    static public function modifieStatutAdmin($conn, $num, $statut) {
        if ($statut === true)
            $query = "UPDATE personnes set estAdmin = true WHERE NumNational = :num";
        else
            $query = "UPDATE personnes set estAdmin = false WHERE NumNational = :num";
        $sth = $conn->prepare($query);
        $sth->execute([ ':num' => $num ]);
        if ($sth->rowCount() !== 1)
            throw new Exception("Impossible de changer le statut estAdmin de l'utilisateur");
    }

    static public function creeOuMAJ($conn, $user) {
        $query = "INSERT INTO personnes (name, Email, NumNational) VALUES (:name, :email, :numNational) ON DUPLICATE KEY UPDATE name = :name, email = :email";
        $sth = $conn->prepare($query);
        $sth->execute([ ':name' => $user['name'], ':email' => $user['mail'], ':numNational' => $user['number'] ]);
        $conn->commit();
    }

    static public function load($conn, $num) {
        $query = "SELECT * FROM personnes WHERE NumNational = :num";
        $sth = $conn->prepare($query);
        $sth->execute([ ':num' => $num ]);
        if ($sth->rowCount() !== 1)
            throw new Exception("Utilisateur inconnu");
        return $sth->fetchAll()[0];
    }

    static public function getAll($conn) {
        $query = "SELECT * FROM personnes ORDER BY NumNational";
        $sth = $conn->prepare($query);
        $sth->execute();
        return $sth->fetchAll();
    }

    static public function estAdmin($conn, $numGivav) {
        $query = "SELECT 1 FROM personnes WHERE NumNational = :num AND estAdmin IS true";
        $sth = $conn->prepare($query);
        $sth->execute([ ':num' => $numGivav ]);
        return $sth->rowCount() === 1;
    }
}
