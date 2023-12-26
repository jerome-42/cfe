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
        $response = curl_exec($ch);
        if (curl_errno($ch))
            throw new Exception(curl_error($ch));
            
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code != 200)
            throw new Exception("GIVAV a retourné une erreur, attendez et ré-essayez à nouveau");
        if ($response == 'Veuillez saisir un numéro national ou une adresse de courriel.')
            throw new Exception($response);
        if ($response == "OK")
            return true;
    }
}
