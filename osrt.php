<?php

/*
 * /index.php avec login et password
 * en retour on a un 302 avec un GET d'un id + id (sorte de session)
 * par exemple https://osrt.g-nav.org/main.php?user=xxxx&page=choixRole&id=yyyy
 * et c'est cette page qui donne le cookie !
 * et cette page affiche la liste des roles disponibles (Responsable d'entretien ...)
 * chaque rôle emmène vers une page type https://osrt.g-nav.org/main.php?user=xxxx&page=mainResp&id=yyyy&adh=0&role=RESP
 * qui donne la liste des aéronefs
 */

class OSRT {
    private $fqdn = 'https://osrt.g-nav.org/';
    private $login;
    public function __construct() {

    }

    private function fromRoleGetGliders($roleURL) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getAbsoluteURL($roleURL));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID='.$this->session);
        $response = curl_exec($ch);
        if (curl_errno($ch))
            throw new Exception(curl_error($ch));

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code != 200)
            throw new Exception("OSRT a retourné une erreur, attendez et ré-essayez à nouveau");
        return $this->fromRoleGetGlidersParseHTML($response);
    }

    private function fromRoleGetGlidersParseHTML($html) {
        $immats = [];
        $dom = new DomDocument();
        @$dom->loadHTML($html); // le parsing produit des warnings, on les ignore
        $xpath = new DOMXpath($dom);        
        $nodes = $xpath->query("//table[@id='mainResp']/tr/td/a");
        foreach ($nodes as $node) {
            if ($node->getAttribute('href') != '') {
                $link = $node->getAttribute('href');
                $immatNode = $xpath->query("//td[@id='mainRespCol1']", $node->parentNode->parentNode);
                $immat = trim($immatNode[0]->textContent);
                $immats[] = [ 'immat' => $immat, 'link' => $link ];
            }
        }
        if (count($immats) === 0)
            throw new Exception("pas de machines trouvées pour ".$this->login);
        $glidersDetails = [];
        foreach ($immats as $immat) {
            $glidersDetails[] = array_merge([ 'immat' => $immat['immat'] ],
                                            $this->getDetailsFromImmat($immat['link'], $immat['immat']));
        }
        return $glidersDetails;
    }

    private function getDetailsFromImmat($immatURL, $immat) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getAbsoluteURL($immatURL));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID='.$this->session);
        $response = curl_exec($ch);
        if (curl_errno($ch))
            throw new Exception(curl_error($ch));

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code != 200)
            throw new Exception("OSRT a retourné une erreur, attendez et ré-essayez à nouveau");
        return $this->getDetailsFromImmatParseHTML($response, $immat);
    }

    public function getDetailsFromImmatParseHTML($html, $immat) {
        $toRet = [];
        // CEN
        if (preg_match_all('/expire le <span id="infoGeneCardex">([\d\/]+)/', $html, $matches) === 1) {
            //DEBUG echo "$immat get CEN: ".$matches[1][0].PHP_EOL;
            list($day, $month, $year) = explode('/', $matches[1][0]);
            $d = new DateTime();
            $d->setDate($year, $month, $day);
            $toRet['cenExpirationDate'] = $d;
        }
        // APRS
        $dom = new DomDocument();
        @$dom->loadHTML($html); // le parsing produit des warnings, on les ignore
        $xpath = new DOMXpath($dom);        
        $nodes = $xpath->query("//table[@id='TourOngletStep1-1']/tr/td");
        foreach ($nodes as $node) {
            if ($node->textContent === 'Visite Annuelle') {
                $dateString = $node->nextElementSibling->textContent;
                //DEBUG echo "$immat get APRS: ".$dateString.PHP_EOL;
                list($day, $month, $year) = explode('/', $dateString);
                $d = new DateTime();
                $d->setDate($year, $month, $day);
                $toRet['aprsExpirationDate'] = $d;
                break;
            }
        }
        
        return $toRet;
    }

    public function getGlidersDetails() {
        $glidersDetails = [];
        foreach ($this->roles as $role) {
            $glidersDetails = array_merge($glidersDetails, $this->fromRoleGetGliders($role));
        }
        return $glidersDetails;
    }

    public function login($login, $password) {
        $this->login = $login;
        $url = $this->fqdn.'index.php';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [ 'codeGnav' => $login, 'password' => $password, 'action' => 'action' ]);
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

        if ($http_code != 302)
            throw new Exception("éléments d'identification OSRT pour le login ".$login." incorrect");

        preg_match_all('/^Location:\s*(.+)$/mi', $header, $matches);
        $nextURL = trim($matches[1][0]);
        // normalement OSRT retourne une URL relative, mais sait-on jamais
        $nextURL = $this->getAbsoluteURL($nextURL);
        return $this->loginStep2($login, $password, $nextURL);
    }

    private function loginStep2($login, $password, $url) {
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

        if ($http_code != 200)
            throw new Exception("éléments d'identification OSRT pour le login ".$login." incorrect");

        // on récupère le cookie d'authentification pour pouvoir demander la page
        // qui affiche le numéro givav, le nom + prénom tout en étant connecté à givag
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
        $cookies = array();
        foreach($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }
        $this->session = $cookie['PHPSESSID'];
        $this->parseRoles($body);
    }

    private function parseRoles($html) {
        $roles = [];
        $dom = new DomDocument();
        @$dom->loadHTML($html); // le parsing produit des warnings, on les ignore
        $xpath = new DOMXpath($dom);        
        $links = $xpath->query("//table[@class='listeSansCadre1']/tr/td/a");
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $roles[] = $href;
        }
        if (count($roles) === 0)
            throw new Exception("pas de roles OSRT pour ce le compte ".$this->login);
        $this->roles = $roles;
    }

    private function getAbsoluteURL($url) {
        if (strpos($url, 'http') === false)
            return $this->fqdn.$url;
        return $url;
    }
}
