<?php

class Flarm {
    private $conn;

    static public function aircraftTypeToText($type) {
        switch (intval($type)) {
        case 1:
            return "planeur";
        case 2:
            return "remorqueur";
        case 3:
            return "hélicoptère";
        case 4:
            return "parachutiste";
        case 5:
            return "deltaplane";
        case 6:
            return "parapente";
        case 7:
            return "avion";
        case 8:
            return "jet";
        case 9:
            return "OVNI";
        case 10:
            return "ballon";
        case 11:
            return "dirigeable";
        case 12:
            return "drone";
        case 13:
            return "fixe";
        default:
            return "inconnu";
        }
    }

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function checkRange($filename, $igcData) {
        $url = 'https://shop.flarm.com/support/tools-software/flarm-range-analyzer/range-analyzer-files-upload';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        $igc = new \CURLStringFile($igcData, $filename, 'application/octet-stream');
        $headers = [ "Content-Type" => "multipart/form-data" ];
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [ 'igcfile[0]' => $igc, 'action' => 'shortTerm' ]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        if (curl_errno($ch))
            throw new Exception(curl_error($ch));

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        curl_close($ch);
        if ($http_code != 200)
            throw new Exception("Réponse inattendue de flarm.com");
        $res = preg_match_all('/href="https:\/\/shop\.flarm\.com\/support\/tools-software\/flarm-range-analyzer\/range-analyzer-results\/\?0=(\w+\.IGC)"/m', $response, $matches);
        if ($res === false)
            throw new Exception("Réponse inattendue de flarm.com");
        // le fichier n'est pas géré par flarm
        if ($res === 0)
            return [ 'stealth' => '0', 'noTrack' => '0', 'radioId' => 'NA', 'flarmResultUrl' => '', 'rangeAvg' => 0, 'rangeBelowMinimum' => 0, 'rangeDetails' => "flarm.com n'a pas réussi à analyser le fichier" ];
        $flarmFilename = $matches[1][0];

        if (preg_match_all('/href="(https:\/\/shop\.flarm\.com\/support\/tools-software\/flarm-range-analyzer\/range-analyzer-results\/\?0=\w+\.IGC)"/m', $response, $matches) === false)
            throw new Exception("Réponse inattendue de flarm.com");

        $url = $matches[1][0];
        return $this->recupereResultats($flarmFilename, $url);
    }

    public function getFlarmLogs($gliderId) {
        $q = "SELECT *, UNIX_TIMESTAMP(`when`) AS `when` FROM flarm_logs WHERE glider = :id ORDER BY `when` DESC";
        $sth = $this->conn->prepare($q);
        $sth->execute([ ':id' => $gliderId ]);
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    private function recupereResultats($flarmFilename, $flarmResultUrl) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://shop.flarm.com/analyzer/parseIgc.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [ 'filesToProcess' => $flarmFilename ]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        if (curl_errno($ch))
            throw new Exception(curl_error($ch));

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code != 200)
            throw new Exception("FLARM a retourné une erreur, attendez et ré-essayez à nouveau");
        curl_close($ch);
        $data = json_decode($response, true);
        $toRet = [
            'stealth' => ($data[0]['stealth'] === 'OFF' ? 0 : 1),
            'noTrack' => ($data[0]['noTrack'] === 'OFF' ? 0 : 1),
            'radioId' => $data[0]['radioId'],
            'flarmResultUrl' => $flarmResultUrl,
        ];
        // from FLARM https://www.flarm.com/analyzer/js/minimum-range.js
        $minimumRange = [];
        $warningRange = [];
        $mySpeed = 70; // = 250 km/h [m/s]
        $myAlarmRangeSecondBeforeCollision = 18; // [s]
        $myAlarmRangeMeter = $mySpeed * $myAlarmRangeSecondBeforeCollision; // [m]
        $hisSpeed = 70; // = 250 km/h [m/s]
        $hisAlarmRangeSecondBeforeCollision = 18; // [s]
        $hisAlarmRangeMeter = $mySpeed * $myAlarmRangeSecondBeforeCollision; // [m]
        $minimum = 100000;
        $maximum = 0;
        $avg = 0;
        $rangeBelowMinimum = false;
        if (isset($data[0]['shortTerm']['Merged Antennas']['averageRangeBySector'])) {
            foreach ($data[0]['shortTerm']['Merged Antennas']['averageRangeBySector'] as $idx => $val) {
                if ($val < $minimum)
                    $minimum = $val;
                if ($val > $maximum)
                    $maximum = $val;
                $avg += $val;
                $minimumRange[$idx] = $hisAlarmRangeMeter + $myAlarmRangeMeter * sin(abs($idx - 10) / 10 * pi() / 2);
                if ($minimumRange[$idx] >= $val) {
                    $rangeBelowMinimum = true;
                }
            }
            $avg = $avg / count($data[0]['shortTerm']['Merged Antennas']['averageRangeBySector']);
            $toRet = array_merge($toRet, [
                'minium' => $minimum, // [m]
                'maximum' => $maximum, // [m]
                'rangeAvg' => $avg,
                'rangeBelowMinimum' => $rangeBelowMinimum === true ? 1 : 0,
                'rangeDetails' => 'Minimum: '.round($minimum/1000, 1).' km, maximum: '.round($maximum/1000, 1).' km, moyenne: '.round($avg/1000, 1).' km',
            ]);
        }
        return $toRet;
    }

    // OGN nous envoie la version de tous les planeurs
    public function pushFlarmVersionAndRadioIdFromOGN($immat, $radioId, $softwareVersion) {
        $glider = new Gliders($this->conn);
        $gliderData = $glider->getGliderByImmat($immat);
        if ($gliderData == null) {
            // OGN nous envoie tous les planeurs mais si le planeur n'existe pas chez nous on s'arrête ici
            return;
        }
        $ognPersonne = Personne::loadOGN($this->conn); // OGN
        // OGN va nous envoyer plusieurs fois par jour la version soft, on ne l'enregistre que si elle est différente de
        // celle que l'on connaît
        $currentData = $glider->getLastFlarmLog($gliderData['id'], false);
        if ($currentData === null || // pas d'enregistrement flarm
            ($currentData['versionSoft'] != $softwareVersion || // la version soft est différente
             $currentData['radioId'] != $radioId)) {
            $q = "INSERT INTO flarm_logs (glider, `when`, filename, versionSoft, radioId, who) VALUES (:glider, NOW(), 'OGN', :softVersion, :radioId, :who)";
            $sth = $this->conn->prepare($q);
            $sth->execute([ ':glider' => $gliderData['id'],
                            ':softVersion' => $softwareVersion,
                            ':radioId' => $radioId,
                            ':who' => $ognPersonne['id'],
            ]);
        } else if ($currentData != null && $currentData['versionSoft'] == $softwareVersion &&
                   $currentData['radioId'] == $radioId) {
            $ognPersonne = Personne::loadOGN($this->conn);
            if ($currentData['who'] == $ognPersonne['id']) // si c'est une déclaration OGN on met à jour la date
                $this->pushFlarmVersionAndRadioIdFromOGN_updateLastRecord($currentData);
        }
    }

    private function pushFlarmVersionAndRadioIdFromOGN_updateLastRecord($currentData) {
        $q = "UPDATE flarm_logs SET `when` = NOW() WHERE id = :id";
        $sth = $this->conn->prepare($q);
        $sth->execute([ ':id' => $currentData['flarmLogId'] ]);
    }
}
