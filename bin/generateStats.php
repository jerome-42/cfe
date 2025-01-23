#!/bin/env php
<?php

$config = json_decode(file_get_contents(__DIR__.'/../config.json'), true);

function getDateOfLastFlight($db, $year) {
    $q = "SELECT MAX(date_vol) AS d FROM vfr_vol WHERE EXTRACT(YEAR FROM date_vol) = :annee";
    $sth = $db->prepare($q);
    $sth->execute([ ':annee' => $year ]);
    $data = $sth->fetchAll(PDO::FETCH_ASSOC);
    return DateTime::createFromFormat('Y-m-d', $data[0]['d']);
}

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

$dsn = join(';', [ 'host='.$config['givav']['host'], 'dbname='.$config['givav']['database'] ]);
$db = new PDO("pgsql:".$dsn, $config['givav']['username'], $config['givav']['password']);

$now = new DateTime();
$annee = intval($now->format('Y'));
if (isset($argv[1]))
    $annee = intval($argv[1]);
// on va chercher la date de fin
$dateOfLastFlight = getDateOfLastFlight($db, $annee);

$output = [];
$dateDebut = $annee.'-1-1';
$dateFin = $dateOfLastFlight->modify('last day of this month')->format('Y-m-d');
$anneePrecedente = intval($annee)-1;
$dateDebutPrecedente = $anneePrecedente.'-1-1';
$dateFinPrecedente = $dateOfLastFlight->modify("-1 year");
$dateFinPrecedente = $dateFinPrecedente->format('Y-m-d');
$dateFinPrecedentePleine = $anneePrecedente.'-12-31';

echo "Date du dernier vol: ".$dateFin.PHP_EOL;

echo "statsMachines ".$annee.PHP_EOL;
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
    'year' => $annee,
    'requete' => $q,
    'data' => $data,
];

echo "statsMachines ".$anneePrecedente.PHP_EOL;
$q = 'SELECT * FROM statsMachines(:start, :end)';
$sth = $db->prepare($q);
$sth->execute([ ':start' => $dateDebutPrecedente, ':end' => $dateFinPrecedentePleine ]);
$data = $sth->fetchAll(PDO::FETCH_ASSOC);
foreach ($data as &$line) {
    $line['stats'] = json_decode($line['stats']);
}
$output['statsMachinesPrecedente'] = [
    'params' => [
        'date_debut' => $dateDebutPrecedente,
        'date_fin' => $dateFinPrecedentePleine,
    ],
    'year' => $anneePrecedente,
    'requete' => $q,
    'data' => $data,
];


echo "tableau de bord".PHP_EOL;
$q = 'SELECT tableauDeBord() AS tdb';
$sth = $db->prepare($q);
$sth->execute();
$data = $sth->fetchAll(PDO::FETCH_ASSOC)[0];
$data = json_decode($data['tdb']);
$output['tableauDeBord'] = [
    'requete' => $q,
    'data' => $data,
];

$output['tableauDeBordAnnuel'] = [];

foreach ([ 1, 2, 5, 9 ] as $moyenneSurNbAnnee) {
    echo "tableau de bord annuel ".$moyenneSurNbAnnee." ans".PHP_EOL;
    $q = 'SELECT tableauDeBordAnnuel(:annee, :last_computation_date, :moyenne_sur_nb_annee) AS tdb';
    $sth = $db->prepare($q);
    $sth->execute([ ':annee' => $annee, ':last_computation_date' => $dateFin, ':moyenne_sur_nb_annee' => $moyenneSurNbAnnee ]);
    $data = $sth->fetchAll(PDO::FETCH_ASSOC)[0];
    $data = json_decode($data['tdb']);
    $output['tableauDeBordAnnuel'][$moyenneSurNbAnnee] = [
        'params' => [
            'moyenneSurNbAnnee' => $moyenneSurNbAnnee,
            'annee' => $annee,
        ],
        'requete' => $q,
        'data' => $data,
    ];
}

echo "CNB".PHP_EOL;
$q = "SELECT * from etatMachineCNB(:cnb, '".$annee."-01-01', '".$annee."-12-31', true) WHERE immatriculation NOT IN ('D-5345', 'F-CEHD', 'F-CFLX', 'F-CPLE') ORDER BY 1";
$sth = $db->prepare($q);
$params = [ ':cnb' => '20:00:00' ];
$sth->execute($params);
$data = $sth->fetchAll(PDO::FETCH_ASSOC);
$output['CNB'] = [
    'params' => $params,
    'requete' => $q,
    'data' => $data,
];

// CURLStringFile n'existe pas en php 7 donc on triche avec un fichier temporaire
$fichierStats = tempnam(sys_get_temp_dir(), 'stats.js');
file_put_contents($fichierStats, 'var stats = '.json_encode($output).';');


echo "vols anonymisés".PHP_EOL;
$q = "SELECT *,
REPLACE(prix_vol::text, '.', ',') AS prix_vol_fr,
REPLACE(prix_remorque::text, '.', ',') AS prix_remorque_fr,
REPLACE(prix_treuil::text, '.', ',') AS prix_treuil_fr,
REPLACE(prix_moteur::text, '.', ',') AS prix_moteur_fr,
REPLACE(prix_vol_elv::text, '.', ',') AS prix_vol_fr,
REPLACE(prix_remorque_elv::text, '.', ',') AS prix_remorque_elv_fr,
REPLACE(prix_treuil_elv::text, '.', ',') AS prix_treuil_elv_fr,
REPLACE(prix_moteur_elv::text, '.', ',') AS prix_moteur_elv_fr,
REPLACE(prix_vol_cdb::text, '.', ',') AS prix_vol_cdb_fr,
REPLACE(prix_remorque_cdb::text, '.', ',') AS prix_remorque_cdb_fr,
REPLACE(prix_treuil_cdb::text, '.', ',') AS prix_treuil_cdb_fr,
REPLACE(prix_moteur_cdb::text, '.', ',') AS prix_moteur_cdb_fr,
REPLACE(prix_vol_co::text, '.', ',') AS prix_vol_co_fr,
REPLACE(prix_remorque_co::text, '.', ',') AS prix_remorque_co_fr,
REPLACE(prix_treuil_co::text, '.', ',') AS prix_treuil_co_fr,
REPLACE(prix_moteur_co::text, '.', ',') AS prix_moteur_co_fr,
REPLACE(prix_frais_technique_eleve::text, '.', ',') AS prix_frais_technique_eleve,
REPLACE(prix_frais_technique_cdb::text, '.', ',') AS prix_frais_technique_cdb,
REPLACE(prix_frais_technique_co::text, '.', ',') AS prix_frais_technique_co,
ROUND(EXTRACT(EPOCH FROM temps_vol)/60) AS temps_vol_en_minutes
 FROM anonymisationVol(:annee, true)";
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
$q = "SELECT *,
REPLACE(prix_vol::text, '.', ',') AS prix_vol_fr,
REPLACE(prix_remorque::text, '.', ',') AS prix_remorque_fr,
REPLACE(prix_treuil::text, '.', ',') AS prix_treuil_fr,
REPLACE(prix_moteur::text, '.', ',') AS prix_moteur_fr,
REPLACE(prix_vol_elv::text, '.', ',') AS prix_vol_fr,
REPLACE(prix_remorque_elv::text, '.', ',') AS prix_remorque_elv_fr,
REPLACE(prix_treuil_elv::text, '.', ',') AS prix_treuil_elv_fr,
REPLACE(prix_moteur_elv::text, '.', ',') AS prix_moteur_elv_fr,
REPLACE(prix_vol_cdb::text, '.', ',') AS prix_vol_cdb_fr,
REPLACE(prix_remorque_cdb::text, '.', ',') AS prix_remorque_cdb_fr,
REPLACE(prix_treuil_cdb::text, '.', ',') AS prix_treuil_cdb_fr,
REPLACE(prix_moteur_cdb::text, '.', ',') AS prix_moteur_cdb_fr,
REPLACE(prix_vol_co::text, '.', ',') AS prix_vol_co_fr,
REPLACE(prix_remorque_co::text, '.', ',') AS prix_remorque_co_fr,
REPLACE(prix_treuil_co::text, '.', ',') AS prix_treuil_co_fr,
REPLACE(prix_moteur_co::text, '.', ',') AS prix_moteur_co_fr,
REPLACE(prix_frais_technique_eleve::text, '.', ',') AS prix_frais_technique_eleve,
REPLACE(prix_frais_technique_cdb::text, '.', ',') AS prix_frais_technique_cdb,
REPLACE(prix_frais_technique_co::text, '.', ',') AS prix_frais_technique_co,
ROUND(EXTRACT(EPOCH FROM temps_vol)/60) AS temps_vol_en_minutes
 FROM anonymisationVol(:annee, false)";
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
