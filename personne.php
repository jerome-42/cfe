<?php

class Personne {
    static public function loadOGN($conn) {
        $query = "SELECT * FROM personnes WHERE name = 'OGN'";
        $sth = $conn->prepare($query);
        $sth->execute([ ]);
        if ($sth->rowCount() !== 1)
            throw new Exception("L'utilisateur OGN n'existe pas");
        return $sth->fetchAll()[0];
    }

    static public function modifieStatutAdmin($conn, $num, $statut) {
        if ($statut === true)
            $query = "UPDATE personnes set isAdmin = true WHERE givavNumber = :num";
        else
            $query = "UPDATE personnes set isAdmin = false WHERE givavNumber = :num";
        $sth = $conn->prepare($query);
        $sth->execute([ ':num' => $num ]);
        if ($sth->rowCount() !== 1)
            throw new Exception("Impossible de changer le statut estAdmin de l'utilisateur");
    }

    static public function creeOuMAJ($conn, $user) {
        $query = "INSERT INTO personnes (name, email, givavNumber) VALUES (:name, :email, :num) ON DUPLICATE KEY UPDATE name = :name, email = :email";
        $sth = $conn->prepare($query);
        $sth->execute([ ':name' => $user['name'], ':email' => $user['mail'], ':num' => $user['number'] ]);
        $data = self::load($conn, $user['number']);
        $conn->commit();
        return $data; // on a besoin à minima d'id (pour /connexion)
    }

    static public function getFromId($conn, $id) {
        $query = "SELECT * FROM personnes WHERE id = :id";
        $sth = $conn->prepare($query);
        $sth->execute([ ':id' => $id ]);
        if ($sth->rowCount() !== 1)
            throw new Exception("Utilisateur inconnu");
        return $sth->fetchAll()[0];
    }

    static public function load($conn, $num) {
        $query = "SELECT *, personnes.id AS id FROM personnes LEFT JOIN cfe_todo ON cfe_todo.who = personnes.givavNumber WHERE givavNumber = :num";
        $sth = $conn->prepare($query);
        $sth->execute([ ':num' => $num ]);
        if ($sth->rowCount() !== 1)
            throw new Exception("Utilisateur inconnu");
        return $sth->fetchAll()[0];
    }

    static public function getAll($conn, $year) {
        $query = "SELECT *, COALESCE(cfe_todo.todo, settings.value) AS cfeTODO FROM personnes LEFT JOIN cfe_todo ON cfe_todo.who = personnes.givavNumber JOIN settings ON what = :what WHERE cfe_todo.year IS NULL OR cfe_todo.year = :year";
        $sth = $conn->prepare($query);
        $sth->execute([ ':what' => 'defaultCFE_TODO_'.$year, ':year' => $year ]);
        return $sth->fetchAll();
    }

    static public function estAdmin($conn, $numGivav) {
        $query = "SELECT 1 FROM personnes WHERE givavNumber = :num AND isAdmin IS true";
        $sth = $conn->prepare($query);
        $sth->execute([ ':num' => $numGivav ]);
        return $sth->rowCount() === 1;
    }
}
