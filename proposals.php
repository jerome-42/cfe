<?php

class Proposals {
    public function __construct($env) {
        $this->env = $env;
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
        $query = "INSERT INTO cfe_proposals (who, registerDate, priority, title, workType, beneficiary, details, notes, isActive) VALUES (:who, NOW(), :priority, :title, :workType, :beneficiary, :details, :notes, ".$isActive.')';
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
            $query = "INSERT INTO cfe_proposals (who, registerDate, priority, title, workType, beneficiary, details, notes, notValidAfterDate, isActive) VALUES (:who, NOW(), :priority, :title, :workType, :beneficiary, :details, :notes, FROM_UNIXTIME(:notValidAfterDate), ".$isActive.')';
            $params[':notValidAfterDate'] = $data['notValidAfterDate'];
        }
        $sth = $this->env->mysql->prepare($query);
        $sth->execute($params);
    }

    public function update($id, $data) {
        $isActive = $data['isActive'] === true ? 'true' : 'false';
        $query = "UPDATE cfe_proposals SET registerDate = NOW(), who = :who, priority = :priority, title = :title, workType = :workType, beneficiary = :beneficiary, details = :details, isActive = TRUE, notValidAfterDate = NULL, notes = :notes, isActive = ".$isActive." WHERE id = :id";
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
            $query = "UPDATE cfe_proposals SET registerDate = NOW(), who = :who, priority = :priority, title = :title, workType = :workType, beneficiary = :beneficiary, details = :details, notes = :notes,isActive = ".$isActive.", notValidAfterDate = FROM_UNIXTIME(:notValidAfterDate) WHERE id = :id";
            $params[':notValidAfterDate'] = $data['notValidAfterDate'];
        }
        $sth = $this->env->mysql->prepare($query);
        $sth->execute($params);
    }
}
