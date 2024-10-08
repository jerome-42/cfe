<?php

class Givav {
    private $login;
    private $password;
    private $session;

    public function __construct($login, $password) {
        $this->login = $login;
        $this->password = $password;
    }

    public function loginApp() {
        // on doit récupérer l'id de session, donc on demande https://club.givav.fr/givav.php/?assoc=119501
        // et on récupère l'id de session
        list($http_code, $header, $body) = $this->get('https://club.givav.fr/givav.php/?assoc=119501');
        if ($http_code != 200)
            throw new Exception($body);

        // on récupère le numéro de session
        $this->sessionNo = $this->getSessionNo($body);

        // et le cookie
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $header, $matches);
        $cookies = array();
        foreach($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }
        $this->session = $cookies['PHPSESSID'];
        $this->loginAppStep2();
    }

    // maintenant que l'on a le numéro de session on va pouvoir s'authentifier
    private function loginAppStep2() {
        list($http_code, $header, $body) = $this->postFormWithSession('https://club.givav.fr/givav.php/gvdefault/main/login?sessid='.$this->sessionNo.'&assoc=119501', [ 'login_code_acces' => $this->login, 'login_mot_de_passe' => $this->password ]);
        if ($http_code != 200)
            throw new Exception($body);
        if ($body !== 'OK|')
            throw new Exception($body);
    }

    public function fetchDebtors() {
        list($http_code, $header, $body) = $this->getWithSession('https://club.givav.fr/givav.php/gvmembre/tableau/debiteur?sessid='.$this->sessionNo.'&assoc=119501&inactif=0');
        if ($http_code != 200)
            throw new Exception($body);
        $dom = new DomDocument();
        @$dom->loadHTML($body); // le parsing produit des warnings, on les ignore
        $xpath = new DOMXpath($dom);        
        $nodes = $xpath->query("//table[@id='gvmembreTableauDebiteurTable']/tbody/tr");
        $ret = [];
        foreach ($nodes as $node) {
            $givavNumber = null;
            $pilotName = null;
            for ($i = 0; $i < $node->childNodes->length; $i++) {
                $td = $node->childNodes->item($i);
                if ($td->nodeName === 'td') {
                    if ($i === 1 && is_numeric($td->textContent)) // numéro givav
                        $givavNumber = intval($td->textContent);
                    if ($i === 3) // nom du pilote
                        $piloteName = intval($td->textContent);
                    $number = str_replace(',', '.', $td->textContent);
                    if ($i === 5 && is_numeric($number) && $givavNumber !== null) // solde du compte (en €)
                        $ret[] = [ 'givavNumber' => $givavNumber, 'name' => $piloteName, 'balance' => -floatval($number) ];
                }
            }
        }
        return $ret;
    }

    public function downloadBackup() {
        list($http_code, $header, $body) = $this->postFormWithSession('https://club.givav.fr/givav.php/gvparam/assoc/sauvegarde?sessid='.$this->sessionNo.'&assoc=119501&onglet=0', []);
        if (preg_match_all("/sauvegarde\('(\w+)'/m", $body, $matches) !== false) {
            if (count($matches) === 0)
                throw new Exception("GIVAV ne liste aucun fichier de sauvegarde, bug de GIVAV");
            $backupFilename = tempnam('/tmp/', 'givav-backup-');
            if ($backupFilename === false)
                throw new Exception("Unable to get a temporary filename in /tmp path");
            $fh = fopen($backupFilename, 'w+');
            foreach ($matches[1] as $no) {
                echo "Téléchargement de la partie ".$no.PHP_EOL;
                $fhPart = fopen($backupFilename, 'a');
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://club.givav.fr/givav.php/gvparam/assoc/sauvegarde-Part?sessid='.$this->sessionNo.'&assoc=119501&onglet=0&part='.$no);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($ch, CURLOPT_TIMEOUT, 120);
                curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID='.$this->session);
                curl_setopt($ch, CURLOPT_FILE, $fhPart);
                $response = curl_exec($ch);
                if (curl_errno($ch))
                    throw new Exception(curl_error($ch));
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($http_code != 200)
                    throw new Exception("impossible de télécharger la sauvegarde partie ".$no.": ".$http_code);
                curl_close($ch);
                fclose($fhPart);
            }
            fclose($fh);
            return $backupFilename;
        }
        throw new Exception("le code de givav a dû changer, impossible de déclencher la sauvegarde de la base de données");
    }

    private function get($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $response = curl_exec($ch);
        if (curl_errno($ch))
            throw new Exception(curl_error($ch));

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        return [ $http_code, $header, $body ];
    }

    private function getWithSession($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID='.$this->session);
        $response = curl_exec($ch);
        if (curl_errno($ch))
            throw new Exception(curl_error($ch));

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        return [ $http_code, $header, $body ];
    }

    private function postFormWithSession($url, $data) {
        //DEBUG var_dump($url);
        //DEBUG var_dump($data);
        //DEBUG var_dump($this->session);
        $postData = [];
        foreach ($data as $key => $value) {
            $postData[] = $key.'='.urlencode($value);
        }
        $postData = implode('&', $postData);
        //DEBUG var_dump($postData);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Content-Type: application/x-www-form-urlencoded' ]);
        curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID='.$this->session);
        $response = curl_exec($ch);
        if (curl_errno($ch))
            throw new Exception(curl_error($ch));

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        return [ $http_code, $header, $body ];
    }

    private function getSessionNo($body) {
        if (preg_match_all('/\/givav.php\/gvdefault\/main\/login\?sessid=(\d+)/', $body, $matches) !== 1)
            throw new Exception("Le code de la page de login de givav a changé, impossible de trouver l'URL de connexion");
        return $matches[1][0];
    }

    public function updateDebtors($env) {
        $debtors = $this->fetchDebtors();
        $q = "INSERT INTO givavdebtor (givavNumber, balance, since, lastUpdate) VALUES (:givavNumber, :balance, NOW(), NOW()) ON DUPLICATE KEY UPDATE balance = :balance, lastUpdate = NOW()";
        $sth = $env->mysql->prepare($q);
        $givavNumbers = [];
        foreach ($debtors as $debt) {
            $sth->execute([ ':givavNumber' => $debt['givavNumber'], ':balance' => $debt['balance'] ]);
        }
        $env->mysql->query("DELETE FROM givavdebtor WHERE lastUpdate != CAST(NOW() AS date)");
    }
}
