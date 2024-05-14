#!/bin/env php
<?php

$config = json_decode(file_get_contents(__DIR__.'/../config.json'), true);

$output = [];
$now = new DateTime();
$annee = intval($now->format('Y'));
$dateDebut = $annee.'-1-1';
$dateFin = $annee.'-12-31';
$anneePrecedente = intval($annee)-1;
$dateDebutPrecedente = $anneePrecedente.'-1-1';
$dateFinPrecedente = $anneePrecedente.'-12-31';

$dsn = join(';', [ 'host='.$config['givav']['host'], 'dbname='.$config['givav']['database'] ]);
$db = new PDO("pgsql:".$dsn, $config['givav']['username'], $config['givav']['password']);

function uploadFile($config, $what, $file) {
    $url = 'http://cfe-dev.aavo.org/api/pushStatsFile';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);

    $post = [ 'what' => $what, 'data' => curl_file_create($file), 'token' => $config['stats']['token'] ];
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

    $headers = [ "Content-Type" => "multipart/form-data" ];
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
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
    curl_close($ch);
    if ($http_code != 200)
        throw new Exception("Réponse inattendue de cfe.aavo.org: ".$http_code.': '.$body);
}

echo "statsMachines".PHP_EOL;
$q = 'SELECT * FROM statsMachines(:start, :end)';
$sth = $db->prepare($q);
$sth->execute([ ':start' => $dateDebut, ':end' => $dateFin ]);
$data = $sth->fetchAll(PDO::FETCH_ASSOC);
foreach ($data as &$line) {
    $line['stats'] = json_decode($line['stats']);
}
$output['statsMachines'] = [
    'params' => [
        'date_debut' => $dateDebut,
        'date_fin' => $dateFin,
    ],
    'requete' => $q,
    'data' => $data,
];

echo "statsMisesEnLAir ".$annee.PHP_EOL;
$q = 'SELECT * FROM statsMisesEnLAir(:start, :end)';
$sth = $db->prepare($q);
$sth->execute([ ':start' => $dateDebut, ':end' => $dateFin ]);
$data = $sth->fetchAll(PDO::FETCH_ASSOC);
foreach ($data as &$line) {
    $line['stats'] = json_decode($line['stats']);
}
$output['statsMisesEnLAir'] = [
    'params' => [
        'date_debut' => $dateDebut,
        'date_fin' => $dateFin,
    ],
    'requete' => $q,
    'data' => $data,
];

echo "statsMisesEnLAir ".$anneePrecedente.PHP_EOL;
$q = 'SELECT * FROM statsMisesEnLAir(:start, :end)';
$sth = $db->prepare($q);
$sth->execute([ ':start' => $dateDebutPrecedente, ':end' => $dateFinPrecedente ]);
$data = $sth->fetchAll(PDO::FETCH_ASSOC);
foreach ($data as &$line) {
    $line['stats'] = json_decode($line['stats']);
}
$output['statsMisesEnLAirAnneePrecedente'] = [
    'params' => [
        'date_debut' => $dateDebut,
        'date_fin' => $dateFin,
    ],
    'requete' => $q,
    'data' => $data,
];

echo "statsForfait".PHP_EOL;
$q = 'SELECT * FROM statsForfait(:annee)';
$sth = $db->prepare($q);
$sth->execute([ ':annee' => $annee ]);
$output['statsForfait'] = [
    'params' => [
        'annee' => $annee,
    ],
    'requete' => $q,
    'data' => $sth->fetchAll(PDO::FETCH_ASSOC),
];

echo "statsMembre".PHP_EOL;
$q = 'SELECT * FROM statsMembre(:annee)';
$sth = $db->prepare($q);
$sth->execute([ ':annee' => $annee ]);
$output['statsMembre'] = [
    'params' => [
        'annee' => $annee,
    ],
    'requete' => $q,
    'data' => $sth->fetchAll(PDO::FETCH_ASSOC),
];

echo "statsAuCoursAnnee ".$annee.PHP_EOL;
$q = 'SELECT * FROM statsAuCoursAnnee(:annee)';
$sth = $db->prepare($q);
$sth->execute([ ':annee' => $annee ]);
$data = $sth->fetchAll(PDO::FETCH_ASSOC);
foreach ($data as &$line) {
    $line['stats'] = json_decode($line['stats']);
}
$output['statsAuCoursAnnee'] = [
    'params' => [
        'annee' => $annee,
    ],
    'requete' => $q,
    'data' => $data,
];

echo "statsAuCoursAnnee ".$anneePrecedente.PHP_EOL;
$q = 'SELECT * FROM statsAuCoursAnnee(:annee)';
$sth = $db->prepare($q);
$sth->execute([ ':annee' => $anneePrecedente ]);
$data = $sth->fetchAll(PDO::FETCH_ASSOC);
foreach ($data as &$line) {
    $line['stats'] = json_decode($line['stats']);
}
$output['statsAuCoursAnneePrecedente'] = [
    'params' => [
        'annee' => $annee,
    ],
    'requete' => $q,
    'data' => $data,
];

// CURLStringFile n'existe pas en php 7 donc on triche avec un fichier temporaire
$fichierStats = tempnam(sys_get_temp_dir(), 'stats.js');
file_put_contents($fichierStats, 'var stats = '.json_encode($output).';');


echo "vols anonymisés".PHP_EOL;
$q = 'SELECT * FROM anonymisationVol(:annee, true)';
$sth = $db->prepare($q);
$sth->execute([ ':annee' => $annee ]);
$data = $sth->fetchAll(PDO::FETCH_ASSOC);
$fichierVolsAnonymises = tempnam(sys_get_temp_dir(), 'vols-anonymises.js');
$csv = new SplFileObject($fichierVolsAnonymises, 'w');
// header
$csv->fputcsv(array_keys($data[0]));
foreach ($data as $line) {
    $csv->fputcsv($line);
}
$csv = null; // free

echo "vols".PHP_EOL;
$q = 'SELECT * FROM anonymisationVol(:annee, false)';
$sth = $db->prepare($q);
$sth->execute([ ':annee' => $annee ]);
$data = $sth->fetchAll(PDO::FETCH_ASSOC);
$fichierVols = tempnam(sys_get_temp_dir(), 'vols.js');
$csv = new SplFileObject($fichierVols, 'w');
// header
$csv->fputcsv(array_keys($data[0]));
foreach ($data as $line) {
    $csv->fputcsv($line);
}
$csv = null; // free

try {
    uploadFile($config, 'stats.js', $fichierStats);
    uploadFile($config, 'vols-anonymises.csv', $fichierVolsAnonymises);
    uploadFile($config, 'vols.csv', $fichierVols);
    echo "ok fichiers uploadés".PHP_EOL;
}
finally {
    // on supprime les fichiers temporaires
    unlink($fichierStats);
    unlink($fichierVolsAnonymises);
    unlink($fichierVols);
}
