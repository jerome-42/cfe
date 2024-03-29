<?php

class Personne {
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
        $conn->commit();
    }

    static public function load($conn, $num) {
        $query = "SELECT * FROM personnes LEFT JOIN cfe_todo ON cfe_todo.who = personnes.givavNumber WHERE givavNumber = :num";
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
