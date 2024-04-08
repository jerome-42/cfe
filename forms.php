<?php

class Forms {
    public function __construct($env) {
        $this->env = $env;
    }

    public function deleteAnswer($id) {
        $q = "DELETE FROM forms WHERE id = :id";
        $sth = $this->env->mysql->prepare($q);
        $sth->execute([
            ':id' => $id,
        ]);
    }

    public function exists($viewName) {
        if (preg_match('/^[\w\-]+$/', $viewName) === 0) {
            return false;
        }
        return file_exists(__DIR__.'/view/'.$viewName.'.pug');
    }

    public function getAnswerById($id) {
        $q = "SELECT *, UNIX_TIMESTAMP(`when`) AS `when` FROM forms WHERE id = :id";
        $sth = $this->env->mysql->prepare($q);
        $sth->execute([ ':id' => $id ]);
        if ($sth->rowCount() == 1)
            return $sth->fetchAll(PDO::FETCH_ASSOC)[0];
        return null;
    }

    public function listAnswers() {
        $q = "SELECT forms.*, UNIX_TIMESTAMP(`when`) AS `when`, UNIX_TIMESTAMP(`commentWhen`) AS `commentWhen`, forms.name AS name, personnes.name AS commentBy FROM forms LEFT JOIN personnes ON personnes.id = forms.commentBy ORDER BY `when` ASC";
        $sth = $this->env->mysql->prepare($q);
        $sth->execute();
        $answers = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $answers;
    }

    public function listAnswersbyForm($name) {
        $q = "SELECT forms.*, UNIX_TIMESTAMP(`when`) AS `when`, UNIX_TIMESTAMP(`commentWhen`) AS `commentWhen` FROM forms WHERE name = :name ORDER BY `when` ASC";
        $sth = $this->env->mysql->prepare($q);
        $sth->execute([ ':name' => $name ]);
        $answers = $sth->fetchAll(PDO::FETCH_ASSOC);
        foreach ($answers as &$answer)
            $answer['data'] = json_decode($answer['data']);
        return $answers;
    }

    public function updateAnswer($id, $data, $comment, $commentBy) {
        $q = "UPDATE forms SET data = :data, comment = :comment, commentWhen = NOW(), commentBy = :commentBy WHERE id = :id";
        $sth = $this->env->mysql->prepare($q);
        $sth->execute([
            ':data' => $data,
            ':comment' => $comment,
            ':commentBy' => $commentBy,
            ':id' => $id,
        ]);
    }

    public function storeAnswer($name, $ip, $port, $data) {
        $q = "INSERT INTO forms(name, `when`, data, ip, port) VALUES (:name, FROM_UNIXTIME(:when), :data, :ip, :port)";
        $sth = $this->env->mysql->prepare($q);
        $sth->execute([
            ':name' => $name,
            ':when' => time(),
            ':ip' => $ip,
            ':port' => $port,
            ':data' => json_encode($data),
        ]);
    }

}
