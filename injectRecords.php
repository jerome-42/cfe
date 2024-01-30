<?php

// date = DD/MM/AAAA
// on veut AAAA-MM-DD
function parseDateDDMMAAAA($date) {
    $d = explode(' ', $date);
    $elem = explode('/', $d[0]);
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
        return 31654;
    case "Boura Mmarc":
        return 644;
    }
    $query = "SELECT givavNumber FROM personnes WHERE REPLACE(LOWER(name), '-', ' ') LIKE :name LIMIT 1";
    $sth = $conn->prepare($query);
    $sth->execute([ ':name' => '%'.str_replace('-', '%', strtolower($name)).'%' ]);
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
    $sth = $conn->prepare($query);
    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        $row++;
        if ($row == 1) // skip header
            continue;
        for ($i = 0; $i < count($data); $i++)
            $data[$i] = trim($data[$i]);
        $data[0] = parseDateMMDDAAAA($data[0]); // registerDate
        $data[7] = parseDateDDMMAAAA($data[7]); // workDate
        if ($data[7]['year'] != '2024')
            continue;
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
        $sth->execute([ ':who' => getGivavNumFromName($conn, implode(' ', $name)),
                        ':registerDate' => toDate($data[0]),
                        ':workDate' => toDate($data[7], false),
                        ':workType' => $data[3],
                        ':beneficiary' => $data[6],
                        ':duration' => floatval($data[4]),
                        ':status' => 'submitted',
                        ':details' => implode(' ', $details),
        ]);
    }
    fclose($handle);
}
$conn->commit();
