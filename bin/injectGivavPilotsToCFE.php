#!/bin/env php
<?php

require(__DIR__.'/../lib.php');

$config = json_decode(file_get_contents(__DIR__.'/../config.json'), true);
$dsn = join(';', [ 'host='.$config['givav']['host'], 'dbname='.$config['givav']['database'] ]);
$db = new PDO("pgsql:".$dsn, $config['givav']['username'], $config['givav']['password']);

// id_ffvp peut être nul par exemple PILOTE EXTERIEUR et on ne veut pas celui-là
$q = "SELECT id_personne, CONCAT(nom, ' ', prenom) AS name, courriel_1 AS email, no_national AS \"givavNumber\" FROM vfr_pilote WHERE pilote_actif IS true AND id_ffvp IS NOT NULL AND LOWER(licence_nom) NOT LIKE '%découverte%' AND club_nom LIKE '%AAVO%' ORDER BY 2";
$sth = $db->prepare($q);
$sth->execute();
$data = $sth->fetchAll(PDO::FETCH_ASSOC);
// est-ce sa première saison ? A-t'il fait des vols les années précédantes ?
$q = "SELECT COUNT(*) AS nb FROM vfr_vol WHERE saison < EXTRACT(YEAR FROM NOW()) AND (id_cdt_de_bord = :personne OR id_co_pilote = :personne OR id_eleve = :personne)";
$sth = $db->prepare($q);
foreach ($data as &$line) {
    $sth->execute([ ':personne' => $line['id_personne'] ]);
    if ($sth->rowCount() !== 1)
        throw new Exception("oups");
    if ($sth->fetchAll(PDO::FETCH_ASSOC)[0]['nb'] === 0)
        $line['nouveau_membre'] = true;
    else
        $line['nouveau_membre'] = false;
}

$url = $config['prod']['url'].'/api/updatePilotsList';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);

$post = [ 'pilots' => json_encode($data), 'token' => $config['stats']['token'] ];
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
    throw new Exception("Réponse inattendue de ".$url.": ".$http_code.': '.$body);
