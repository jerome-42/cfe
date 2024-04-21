<?php

class Proposals {
    public function __construct($env) {
        $this->env = $env;
    }

    public function doClose($id) {
        $query = "UPDATE cfe_proposals SET isActive = false WHERE id = :id";
        $sth = $this->env->mysql->prepare($query);
        $sth->execute([ ':id' => $id ]);
    }

    public function get($id) {
        $query = 'SELECT cfe_proposals.*, UNIX_TIMESTAMP(cfe_proposals.notValidAfterDate) AS `notValidAfterDate`, personnes.name AS who, personnes.email AS `whoEmail` FROM cfe_proposals JOIN personnes ON personnes.id = cfe_proposals.who WHERE cfe_proposals.id = :id';
        $sth = $this->env->mysql->prepare($query);
        $sth->execute([ ':id' => $id ]);
        if ($sth->rowCount() === 1)
            return $sth->fetchAll(PDO::FETCH_ASSOC)[0];
        throw new Exception("pas de proposition n°".$id);
    }

    public function list() {
        $query = 'SELECT cfe_proposals.*, personnes.name AS who, personnes.email AS `whoEmail` FROM cfe_proposals JOIN personnes ON personnes.id = cfe_proposals.who ORDER BY isActive DESC, registerDate DESC';
        $sth = $this->env->mysql->prepare($query);
        $sth->execute([ ]);
        $lines = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $lines;
    }

    public function create($data) {
        $isActive = $data['isActive'] === true ? 'true' : 'false';
        $canBeClosedByMember = $data['canBeClosedByMember'] === true ? 'true' : 'false';
        $query = "INSERT INTO cfe_proposals (who, registerDate, priority, title, workType, beneficiary, details, notes, canBeClosedByMember, isActive) VALUES (:who, NOW(), :priority, :title, :workType, :beneficiary, :details, :notes, ".$canBeClosedByMember.", ".$isActive.')';
        $params = [
            ':who' => $data['who'],
            ':priority' => $data['priority'],
            ':title' => $data['title'],
            ':workType' => $data['workType'],
            ':beneficiary' => $data['beneficiary'],
            ':details' => $data['details'],
            ':notes' => $data['notes'],
        ];

        if (isset($data['notValidAfterDate'])) {
            $query = "INSERT INTO cfe_proposals (who, registerDate, priority, title, workType, beneficiary, details, notes, notValidAfterDate, canBeClosedByMember, isActive) VALUES (:who, NOW(), :priority, :title, :workType, :beneficiary, :details, :notes, FROM_UNIXTIME(:notValidAfterDate), ".$canBeClosedByMember.", ".$isActive.')';
            $params[':notValidAfterDate'] = $data['notValidAfterDate'];
        }
        $sth = $this->env->mysql->prepare($query);
        $sth->execute($params);
    }

    public function update($id, $data) {
        $isActive = $data['isActive'] === true ? 'true' : 'false';
        $canBeClosedByMember = $data['canBeClosedByMember'] === true ? 'true' : 'false';
        $query = "UPDATE cfe_proposals SET registerDate = NOW(), who = :who, priority = :priority, title = :title, workType = :workType, beneficiary = :beneficiary, details = :details, isActive = TRUE, canBeClosedByMember = ".$canBeClosedByMember.", notValidAfterDate = NULL, notes = :notes, isActive = ".$isActive." WHERE id = :id";
        $params = [
            ':id' => $id,
            ':priority' => $data['priority'],
            ':title' => $data['title'],
            ':workType' => $data['workType'],
            ':beneficiary' => $data['beneficiary'],
            ':details' => $data['details'],
            ':notes' => $data['notes'],
            ':who' => $data['who'],
        ];

        if (isset($data['notValidAfterDate'])) {
            $query = "UPDATE cfe_proposals SET registerDate = NOW(), who = :who, priority = :priority, title = :title, workType = :workType, beneficiary = :beneficiary, details = :details, notes = :notes,isActive = ".$isActive.", canBeClosedByMember = ".$canBeClosedByMember.", notValidAfterDate = FROM_UNIXTIME(:notValidAfterDate) WHERE id = :id";
            $params[':notValidAfterDate'] = $data['notValidAfterDate'];
        }
        $sth = $this->env->mysql->prepare($query);
        $sth->execute($params);
    }
}
