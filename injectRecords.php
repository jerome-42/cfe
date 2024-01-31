<?php

// date = DD/MM/AAAA
// on veut AAAA-MM-DD
function parseDateDDMMAAAA($date) {
    $d = explode(' ', $date);
    $elem = explode('/', $d[0]);
    if ($elem[2] === '0023') // pour ceux qui ne savent pas saisir une date !
        $elem[2] = '2023';
    if ($elem[1] > 12) // pour ceux qui ne savent pas saisir une date !
        return parseDateMMDDAAAA($date);
    if (count($d) == 1)
        return ['year' => $elem[2], 'month' => $elem[1], 'day' => $elem[0] ];
    else
        return ['year' => $elem[2], 'month' => $elem[1], 'day' => $elem[0], 'time' => $d[1] ];
}

// date = MM/DD/AAAA
// on veut AAAA-MM-DD
function parseDateMMDDAAAA($date) {
    $d = explode(' ', $date);
    $elem = explode('/', $d[0]);
    if ($elem[2] === '0023') // pour ceux qui ne savent pas saisir une date !
        $elem[2] = '2023';
    if (count($d) == 1)
        return ['year' => $elem[2], 'month' => $elem[0], 'day' => $elem[1] ];
    else
        return ['year' => $elem[2], 'month' => $elem[0], 'day' => $elem[1], 'time' => $d[1] ];
}

function toDate($date, $withTime = true) {
    if ($withTime === true && isset($date['time']))
        return $date['year'].'-'.$date['month'].'-'.$date['day'].' '.$date['time'];
    else
        return $date['year'].'-'.$date['month'].'-'.$date['day'];
}

function getGivavNumFromName($conn, $name) {
    switch ($name) {
    case "Poulain Malo":
    case "POULAIN Malo":
        return 31654;
    case "Boura Mmarc":
        return 644;
    case "Gildas LE NAOUR":
        return 40574;
    case "Maoujoud M’hamed":
        return 44413;
    case "Porta-Penhouët Laurence":
    case "PORTA-PENHOUËT Laurence":
    case "PENHOUËT-PORTA Laurence":
    case "PENHOUËT Laurence":
        return 44971;
    case "Leroux Roger.":
        return 709;
    case "Moutetde Pasca":
    case "Moutetde Pascal":
        return 720;
    case "dietln michel":
        return 7677;
    case "Gainnet-herblot Luc":
    case "Galland Pierre-Louis":
    case "GALLAND Pierre-Louis":
    case "LE DELLIOU Patrick":
        return -1;
    }
    $query = "SELECT givavNumber FROM personnes WHERE REPLACE(LOWER(name), '-', ' ') LIKE :name LIMIT 1";
    $sth = $conn->prepare($query);
    $correctedName = str_replace('-', '%', strtolower($name));
    $correctedName = str_replace([ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9 ], '', $correctedName);
    $correctedName = preg_replace('/\s+/', ' ', $correctedName);
    $sth->execute([ ':name' => '%'.$correctedName.'%' ]);
    if ($sth->rowCount() === 1)
        return $sth->fetchAll()[0]['givavNumber'];
    throw new Exception("impossible de trouver le numéro givav de: ".$name);
}

if (!isset($argv[1])) {
    echo "Usage: ".$argv[0]." export.csv\n";
    exit(1);
}

$servername = 'localhost';
$username = 'cfe';
$password = 'cfe';
$conn = new PDO("mysql:host=$servername;dbname=cfe", $username, $password);
$conn->beginTransaction();

$row = 0;
if (($handle = fopen($argv[1], "r")) !== FALSE) {
    $query = "INSERT INTO cfe_records (who, registerDate, workDate, workType, beneficiary, duration, status, details) VALUES (:who, :registerDate, :workDate, :workType, :beneficiary, :duration, :status, :details)";
    $sthSubmitted = $conn->prepare($query);
    $query = "INSERT INTO cfe_records (who, registerDate, workDate, workType, beneficiary, duration, status, statusDate, statusWho, details) VALUES (:who, :registerDate, :workDate, :workType, :beneficiary, :duration, 'validated', NOW(), :statusWho, :details)";
    $sthValidated = $conn->prepare($query);
    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        $row++;
        if ($row == 1) // skip header
            continue;
        for ($i = 0; $i < count($data); $i++)
            $data[$i] = trim($data[$i]);
        $data[0] = parseDateMMDDAAAA($data[0]); // registerDate
        $data[7] = parseDateDDMMAAAA($data[7]); // workDate
        //DEBUG echo "row: ".$row.PHP_EOL;
        //DEBUG var_dump($data);
        $details = [];
        foreach ([ 5, 8 ] as $column) {
            if ($data[$column] !== '')
                $details[] = $data[$column];
        }
        $name = [];
        foreach ([ 1, 2 ] as $column) {
            if ($data[$column] != '')
                $name[] = $data[$column];
        }
        $who = getGivavNumFromName($conn, implode(' ', $name));
        if ($who === -1) // le membre n'est pas inscrit
            continue;
        if ($data[7]['year'] != '2024') {
            $sthValidated->execute([ ':who' => $who,
                                     ':registerDate' => toDate($data[0]),
                                     ':workDate' => toDate($data[7], false),
                                     ':workType' => $data[3],
                                     ':beneficiary' => $data[6],
                                     ':duration' => floatval($data[4]),
                                     ':statusWho' => 695,
                                     ':details' => implode(' ', $details),
            ]);
        } else {
            $sthSubmitted->execute([ ':who' => $who,
                                     ':registerDate' => toDate($data[0]),
                                     ':workDate' => toDate($data[7], false),
                                     ':workType' => $data[3],
                                     ':beneficiary' => $data[6],
                                     ':duration' => floatval($data[4]),
                                     ':status' => 'submitted',
                                     ':details' => implode(' ', $details),
            ]);
        }
    }
    fclose($handle);
}
$conn->commit();
