<?php

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function redirect($to) {
    header('Location: '.$to);
    exit;
}

class CFE {
    private $db;

    public function __construct() {
        try {
            $this->db = new PDO("sqlite:".dirname(__FILE__).'/db.sqlite');
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            echo "Impossible d'accéder à la base de donnée: ".$e->getMessage();
            die();
        }
    }

    private function getCharacterName($id) {
        $stmt = $this->db->prepare("SELECT name FROM character WHERE rowid = :rowid");
        $stmt->bindValue(":rowid", $id);
        $res = $stmt->execute();
        $name = $res->fetchArray(SQLITE3_ASSOC)['name'];
        $res->finalize();
        $stmt->close();
        return $name;
        //throw new Exception("unable to fetch name for ".$id.", this id is not stored into database");
    }

    private function getQuestion($id) {
        $stmt = $this->db->prepare("SELECT id, question, negative FROM rule WHERE id = :id");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        return $stmt->fetchAll()[0];
    }
}

function renderHTML($file) {
    $content = file_get_contents($file);
    echo $content;
}
