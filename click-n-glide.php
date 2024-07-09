<?php

class ClickNGlide {
    public function __construct($token) {
        $this->token = $token;
    }

    // d est un Date
    public function fetchSignups($d) {
        $parameter = $d->format('Y-m-d');
        list($http_code, $header, $body) = $this->get('https://api.clicknglide.com/signups/'.$parameter);
        if ($http_code != 200)
            throw new Exception($body);
        $signups = [];
        $data = json_decode($body, true);
        foreach ($data['fields'] as $field) {
            $section = $field['fieldName'];
            foreach ($field['signups'] as $sectionSignups) {
                if ($sectionSignups['userName'] === null)
                    continue;
                if (!isset($signups[$section]))
                    $signups[$section] = [];
                $signups[$section][] = [ 'firstName' => $sectionSignups['userFirstName'],
                                         'lastName' => $sectionSignups['userName'] ];
            }
        }
        return $signups;
    }

    private function get($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Authorization: '.$this->token ]);
        $response = curl_exec($ch);
        if (curl_errno($ch))
            throw new Exception(curl_error($ch));

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        return [ $http_code, $header, $body ];
    }
}
