<?php

class Forms {
    public function __construct($env) {
        $this->env = $env;
    }

    public function exists($viewName) {
        if (preg_match('/^[\w\-]+$/', $viewName) === 0) {
            return false;
        }
        return file_exists(__DIR__.'/view/'.$viewName.'.pug');
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
