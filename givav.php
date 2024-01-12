<?php

class Givav {
    static function auth($login, $password) {
        $url = 'https://club.givav.fr/givav.php/gvsmart/main/connect';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [ 'no_national' => $login, 'mot_de_passe' => $password ]);
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

        if ($http_code != 200 || $body !== "OK")
            throw new Exception($body);
        if ($response == 'Veuillez saisir un numéro national ou une adresse de courriel.')
            throw new Exception($response);

        // on récupère le cookie d'authentification pour pouvoir demander la page
        // qui affiche le numéro givav, le nom + prénom tout en étant connecté à givag
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
        $cookies = array();
        foreach($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }
        //DEBUG var_dump($cookies);
        // on va chercher le prénom + nom du connecté
        $user = self::getName($cookies['PHPSESSID']);
        return $user;

        throw new Exception("Erreur interne, veuillez contacter l'administrateur");
    }

    private static function getName($session) {
        $url = 'https://club.givav.fr/givav.php/gvsmart/donnee/adresse?sessid=1';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID='.$session);
        $response = curl_exec($ch);
        if (curl_errno($ch))
            throw new Exception(curl_error($ch));
            
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code != 200)
            throw new Exception("GIVAV a retourné une erreur, attendez et ré-essayez à nouveau");
        if ($response == 'Veuillez saisir un numéro national ou une adresse de courriel.')
            throw new Exception($response);
        // dans response on a le code HTML de la page où il y a le numéro GIVAV et le nom + prénom
        // on va récupérer le numéro GIVAV
        $givavNumber = 0;
        $name = 'inconnu';
        $mail = null;
        if (preg_match_all("/GIVAV\)\s:\s(\d+)/m", $response, $matches) == 1) {
            // j'ai le numéro GIVAV dans $matches[0]
            $givavNumber = intval($matches[1][0]);
        }
        if (preg_match_all('/<div class="ui-body ui-body-a">\s+<p>\s+([\w\s]+)<br \/>/m', $response, $matches) == 1) {
            $name = $matches[1][0];
        }
        if (preg_match_all('/Courriel\s+:\s+<a href="mailto:([\w\.-_@]+)"/m', $response, $matches) == 1) {
            if (filter_var($matches[1][0], FILTER_VALIDATE_EMAIL)) {
                $mail = $matches[1][0];
            }
        }
        return [ 'number' => $givavNumber, 'name' => $name, 'mail' => $mail ];
    }
}
