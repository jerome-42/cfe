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
    private $mysql;

    public function __construct($mysql) {
        $this->mysql = $mysql;
    }

    private function doesOSRTNeedToBeContacted($login, $forceUpdate) {
        $cache = new Cache();
        $cacheFilename = $this->getCacheFilename($login);
        if ($cache->doesCacheIsExpired($cacheFilename, 4, $forceUpdate))
            return true;
        return false;
    }

    // 6
    private function fromRoleGetGliders($roleURL) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getAbsoluteURL($roleURL));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
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

    // 7
    private function fromRoleGetGlidersParseHTML($html) {
        $immats = [];
        $dom = new DomDocument();
        @$dom->loadHTML($html); // le parsing produit des warnings, on les ignore
        $xpath = new DOMXpath($dom);        
        $nodes = $xpath->query("//table[@id='mainResp']/tr/td/a");
        foreach ($nodes as $node) {
            if ($node->getAttribute('href') != '') {
                $link = $node->getAttribute('href');
                // il peut y avoir des liens pour accéder à la visite en cours
                // main.php?user=xxx&page=visiteMaintenance
                // on ne veut que les liens de ce type: main.php?user=xxxx&amp;page=situTech
                if (strpos($link, 'situTech') !== false) {
                    $immatNode = $node->parentNode->nextSibling->nextSibling;
                    $immat = trim($immatNode->textContent);
                    //DEBUG echo "glider: ".$immat." ".$link.PHP_EOL;
                    $immats[] = [ 'immat' => $immat, 'link' => $link ];
                }
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

    private function getAbsoluteURL($url) {
        if (strpos($url, 'http') === false)
            return $this->fqdn.$url;
        return $url;
    }

    private function getCacheFilename($login) {
        return 'osrt-'.preg_replace('/[[:^print:]]/', '', $login); // on ne garde que des caractères imprimables
    }

    public function getDatabaseLastUpdate($osrtConfig) {
        $d = null;
        $cache = new Cache();
        foreach ($osrtConfig['credentials'] as $credential) {
            $cacheFilename = $this->getCacheFilename($credential['login']);
            $cacheStatus = $cache->getCacheStatus($cacheFilename);
            if (is_numeric($cacheStatus) && ($d === null || $cacheStatus > $d))
                $d = $cacheStatus;
        }
        return $d;
    }

    // 8
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
        //DEBUG file_put_contents($immat, $response);
        return $this->getDetailsFromImmatParseHTML($response, $immat);
    }

    // 9
    private function getDetailsFromImmatParseHTML($html, $immat) {
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
            // parfois annuelle parfois annuele
            if (preg_match('/visite\s+annuel+e/im', $node->textContent) === 1) {
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

    // 5
    private function getGlidersDetails() {
        $glidersDetails = [];
        foreach ($this->roles as $role) {
            $glidersDetails = array_merge($glidersDetails, $this->fromRoleGetGliders($role));
        }
        return $glidersDetails;
    }

    // 2
    private function login($login, $password) {
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

    // 3
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

    // 4
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

    // 1
    public function updateGliderDetails($login, $password, $forceUpdate) {
        if ($this->doesOSRTNeedToBeContacted($login, $forceUpdate)) {
            $this->login($login, $password);
            $details = $this->getGlidersDetails();
            foreach ($details as $gliderDetails) {
                $setClauses = [];
                $values = [ 'immat' => $gliderDetails['immat'] ];
                foreach ([ 'cenExpirationDate', 'aprsExpirationDate' ] as $key) {
                    if (isset($gliderDetails[$key])) {
                        $setClauses[] = $key.' = FROM_UNIXTIME(:'.$key.')';
                        $values[':'.$key] = $gliderDetails[$key]->getTimestamp();
                    }
                    else
                        $setClauses[] = $key.' = NULL';
                }
                $q = "UPDATE glider SET ".implode(', ', $setClauses)." WHERE immat = :immat";
                //DEBUG var_dump($q, $values);
                $sth = $this->mysql->prepare($q);
                $sth->execute($values);
            }
            $this->updateCacheTimestamp($login);
        }
    }

    private function updateCacheTimestamp($login) {
        $cache = new Cache();
        $cacheFilename = $this->getCacheFilename($login);
        $cache->writeCacheFile($cacheFilename, 'updated at '.date(DATE_RFC2822));
    }
}
