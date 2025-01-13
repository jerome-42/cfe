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

    static public function modifieIsOwnerOfGlider($conn, $num, $isOwnerOfGlider) {
        if ($isOwnerOfGlider === true)
            $query = "UPDATE personnes set isOwnerOfGlider = true WHERE givavNumber = :num";
        else
            $query = "UPDATE personnes set isOwnerOfGlider = false WHERE givavNumber = :num";
        $sth = $conn->prepare($query);
        $sth->execute([ ':num' => $num ]);
        if ($sth->rowCount() !== 1)
            throw new Exception("Impossible de changer le statut isOwnerOfGlider de l'utilisateur");
    }

    static public function modifieStatutNoRevealWhenInDebt($conn, $num, $statut) {
        if ($statut === true)
            $query = "UPDATE personnes set noRevealWhenInDebt = true WHERE givavNumber = :num";
        else
            $query = "UPDATE personnes set noRevealWhenInDebt = false WHERE givavNumber = :num";
        $sth = $conn->prepare($query);
        $sth->execute([ ':num' => $num ]);
        if ($sth->rowCount() !== 1)
            throw new Exception("Impossible de changer le statut noRevealWhenInDebt de l'utilisateur");
    }

    static public function creeOuMAJ($conn, $user) {
        $query = "INSERT INTO personnes (name, email, givavNumber) VALUES (:name, :email, :num) ON DUPLICATE KEY UPDATE name = :name, email = :email";
        $sth = $conn->prepare($query);
        $sth->execute([ ':name' => $user['name'], ':email' => $user['mail'], ':num' => $user['number'] ]);
        $data = self::load($conn, $user['number']);
        $conn->commit();
        return $data; // on a besoin à minima d'id (pour /connexion)
    }

    static public function creeSiNecessaire($conn, $user) {
        $query = "INSERT IGNORE INTO personnes (name, email, givavNumber) VALUES (:name, :email, :num)";
        $sth = $conn->prepare($query);
        $sth->execute([ ':name' => $user['name'], ':email' => $user['mail'], ':num' => $user['number'] ]);
        $data = self::load($conn, $user['number']);
        $conn->commit();
        return $data; // on a besoin à minima d'id (pour /connexion)
    }

    // dans signups on a: { 'Instructeur': [ 'Prénom Nom', 'Prénom Nom2' }, 'Chef de piste': [ 'Prénom Nom' ] }
    // d est la date de l'inscription
    static public function getDebtPilotFromClicnNGlideSignups($conn, $d, $signups) {
        $pilots = [];
        $notResolved = [];
        // certains prénoms sont avec des - d'autres non (JEAN-PIERRE, JEAN-LUC ...) on gère les 2 cas
        $q = "SELECT personnes.name, personnes.noRevealWhenInDebt, personnes.email, personnes.givavNumber, givavdebtor.balance, unix_timestamp(givavdebtor.since) AS since FROM personnes LEFT JOIN givavdebtor ON givavdebtor.givavNumber = personnes.givavNumber WHERE (personnes.name LIKE :name OR REPLACE(:name, '-', ' ') = personnes.name) AND (givavdebtor.since <= :d OR givavdebtor.since IS NULL)";
        $sth = $conn->prepare($q);
        //var_dump($signups);
        foreach ($signups as $section => $names) {
            // on ne vérifie pas les remorqueurs, parfois ils sont pilotes extérieurs
            if ($section === 'Remorqueurs')
                continue;
            foreach ($names as $name) {
                $nameToBeDisplayed = $name['firstName'] . ' ' . $name['lastName'];
                $givavName = strtoupper($name['lastName'] . ' ' . $name['firstName']);
                $sth->execute([ ':name' => $givavName, ':d' => $d->format("Y-m-d") ]);
                //DEBUG echo json_encode($name)." ".$givavName.$d->format("Y-m-d")."\n";
                //DEBUG echo "res: ".$sth->rowCount()."n";
                // on n'affiche pas les stagiaires
                if ($sth->rowCount() === 0 && strpos($nameToBeDisplayed, "Stagiaire") !== false)
                    continue;
                if ($sth->rowCount() === 0)
                    $notResolved[] = $nameToBeDisplayed." est inconnu";
                if ($sth->rowCount() > 1) {
                    $personnes = [];
                    foreach ($sth->fetchAll() as $line)
                        $personnes[] = $line['name'];
                    $notResolved[] = $nameToBeDisplayed." renvoi plusieurs lignes depuis la table personnes ".implode(', ', $personnes);
                }
                if ($sth->rowCount() === 1) {
                    $row = $sth->fetchAll()[0];
                    //DEBUG var_dump($row);
                    // on est ok pour que le pilote ne soit pas listé même s'il est en négatif
                    if ($row['noRevealWhenInDebt'] === 1)
                        continue;

                    if ($row['balance'] !== null) {
                        if (!isset($pilots[$nameToBeDisplayed]))
                            $pilots[$nameToBeDisplayed] = [ 'name' => $nameToBeDisplayed, 'email' => $row['email'], 'givavNumber' => $row['givavNumber'], 'balance' => floatval($row['balance']), 'since' => $row['since'], 'sections' => [] ];
                        $pilots[$nameToBeDisplayed]['sections'][] = $section;
                    }
                }
            }
        }
        return [ $pilots, $notResolved ];
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
        $query = "SELECT *, COALESCE(cfe_todo.todo, settings.value) AS cfeTODO, va.minutes AS vaMaxi
FROM personnes
JOIN personnes_active ON personnes_active.id_personne = personnes.id AND personnes_active.year = :year
LEFT JOIN cfe_todo ON cfe_todo.who = personnes.givavNumber
LEFT JOIN va ON va.who = personnes.givavNumber
JOIN settings ON what = :what
WHERE cfe_todo.year IS NULL OR cfe_todo.year = :year ORDER BY name";
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

    static public function setActive($conn, $idPersonne) {
        $q = "INSERT IGNORE INTO personnes_active (id_personne, year) VALUES (:id_personne, :year)";
        $sth = $conn->prepare($q);
        $sth->execute([ ':id_personne' => $idPersonne, ':year' => $year ]);
        return $sth->rowCount() === 1;
    }
}
