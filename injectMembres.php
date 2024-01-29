<?php

if (!isset($argv[1])) {
    echo "Usage: ".$argv[0]." membre.csv\n";
    exit(1);
}

$servername = 'localhost';
$username = 'cfe';
$password = 'cfe';
$conn = new PDO("mysql:host=$servername;dbname=cfe", $username, $password);
$conn->beginTransaction();

$row = 0;
if (($handle = fopen($argv[1], "r")) !== FALSE) {
    $query = "INSERT INTO personnes (name, givavNumber, email) VALUES (:name, :num, :email)";
    $sth = $conn->prepare($query);
    $query = "INSERT INTO personnes (name, givavNumber) VALUES (:name, :num)";
    $sthWithoutMail = $conn->prepare($query);
    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        $row++;
        if ($row == 1) // skip header
            continue;
        for ($i = 0; $i < count($data); $i++)
            $data[$i] = trim($data[$i]);
        //DEBUG echo "row: ".$row.PHP_EOL;
        //DEBUG var_dump($data);
        if ($data[3] !== '') {
            $sth->execute([ ':name' => $data[1].' '.$data[2],
                            ':num' => intval($data[0]),
                            ':email' => $data[3],
            ]);
        } else {
            $sthWithoutMail->execute([ ':name' => $data[1].' '.$data[2],
                                       ':num' => intval($data[0]),
            ]);
        }
    }
    fclose($handle);
}
$conn->commit();
