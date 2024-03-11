<?php

// from https://github.com/phprouter/main

include_once __DIR__ . '/router.php';
include_once __DIR__ . '/cfe.php';
include_once __DIR__ . '/csv.php';
include_once __DIR__ . '/givav.php';
include_once __DIR__ . '/personne.php';
include_once __DIR__ . '/vendor/autoload.php';

function displayError($pug, $message) {
    http_response_code(500);
    $vars = array_merge($_SESSION, [ 'message' => $message ]);
    $pug->displayFile('view/error.pug', $vars);
}

get('/', function($conn, $pug) {
    if (!isset($_SESSION['auth'])) {
        return redirect('/connexion');
    }
    $cfe = new CFE($conn);
    $vars = array_merge($_SESSION, $cfe->getStats($_SESSION['givavNumber'], getYear()));
    $vars['durationSubmitted'] = $cfe->getSubmittedDuration();
    $pug->displayFile('view/index.pug', $vars);
});

get('/error', function($conn) {
    throw new Exception("test d'erreur");
});

post('/changeAdmin', function($conn) {
    if (!isset($_SESSION['auth'])) {
        echo "vous n'êtes pas connecté";
        return http_response_code(500);
    }
    if (!isset($_SESSION['isAdmin'])) {
        echo "vous n'êtes pas admin";
        return http_response_code(500);
    }
    foreach ([ 'num', 'status' ] as $elem) {
        if (!isset($_POST[$elem]) || $_POST[$elem] === '') {
            echo "le paramètre ".$elem." est absent";
            return http_response_code(500);
        }
    }
    if (!is_numeric($_POST['num'])) {
        echo "le paramètre num doit être un entier";
        return http_response_code(500);
    }
    $status = false;
    if ($_POST['status'] === 'true')
        $status = true;
    Personne::modifieStatutAdmin($conn, intval($_POST['num']), $status);
});

get('/connexion', function($conn, $pug) {
    if (isset($_SESSION['auth'])) {
        return redirect('/');
    }
    $pug->displayFile('view/connexion.pug');
});

post('/connexion', function($conn, $pug) {
    $vars = [];
    if (!isset($_POST['login']) || $_POST['login'] === '') {
        $vars['error'] = "Veuillez saisir votre n°nationnal ou courriel";
        return $pug->displayFile('view/connexion.pug', $vars);
    }
    if (!isset($_POST['pass']) || $_POST['pass'] === '') {
        $vars['error'] = "Veuillez saisir votre mot de passe";
        return $pug->displayFile('view/connexion.pug', $vars);
    }
    try {
        $user = Givav::auth($_POST['login'], $_POST['pass']);
        Personne::creeOuMAJ($conn, $user);
        $_SESSION['auth'] = true;
        $_SESSION['givavNumber'] = $user['number'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['mail'] = $user['mail'];
        syslog(LOG_INFO, "CFE ".getClientIP()." ".$user['number']." ".$user['name']." logged");
        return redirect('/');
    }
    catch (Exception $e) {
        $vars['error'] = $e->getMessage();
        return $pug->displayFile('view/connexion.pug', $vars);
    }
    $vars['error'] = "Pilote inconnu du GIVAV";
    $pug->displayFile('view/connexion.pug', $vars);
});

get('/declaration', function($conn, $pug) {
    if (!isset($_SESSION['auth'])) {
        return redirect('/connexion');
    }
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $vars = $_SESSION;
        $cfe = new CFE($conn);
        $line = $cfe->getLine(intval($_GET['id']));
        if ($line === null) {
            return displayError($pug, "Déclaration inconnue");
        }
        if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] === false) {
            // un non-admin ne peut éditer qu'une déclaration à lui et uniquement submitted
            if ($line['who'] !== $_SESSION['givavNumber']) {
                return displayError($pug, "Déclaration inconnue");
            }
            if ($line['status'] !== 'submitted') {
                return displayError($pug, "Vous ne pouvez pas éditer une déclaration validée ou rejetée");
            }
        } else {
            $vars['personne'] = Personne::load($conn, $line['who']);
        }
        $line['durationHour'] = floor(intval($line['duration']) / 60);
        $line['durationMinute'] = intval($line['duration']) % 60.0;
        $vars['line'] = $line;
        return $pug->displayFile('view/declaration.pug', $vars);
    }
    $vars = $_SESSION;
    $vars['line'] = [
        'id' => '',
        'who' => $_SESSION['givavNumber'],
        'workDate' => date('Y-m-d'), // par défaut c'est la date du jour
        'workType' => '',
        'beneficiary' => '',
        'durationHour' => 0,
        'durationMinute' => 0,
        'details' => '',
    ];
    $pug->displayFile('view/declaration.pug', $vars);
});

post('/declaration', function($conn, $pug) {
    if (!isset($_SESSION['auth'])) {
        return redirect('/connexion');
    }
    // vérification que tous les éléments du formulaire ont été saisi (dateCFE, type, beneficiaire, duree)
    foreach ([ 'startDateCFE', 'stopDateCFE', 'type', 'beneficiary', 'durationHour', 'durationMinute' ] as $elem) {
        if (!isset($_POST[$elem]) || $_POST[$elem] === '') {
            return displayError($pug, $elem." n'est pas présent dans la requête");
        }
    }
    $startDate = new DateTime();
    $stopDate = new DateTime();
    $now = new DateTime();
    // start
    $startDate->setTimestamp(intval($_POST['startDateCFE']));
    if ($startDate->format('Y') !== $now->format('Y')) {
        return displayError($pug, "L'année de déclaration doit être l'année en cours");
    }
    if ($startDate > $now) {
        return displayError($pug, "Impossible de pré-déclarer");
    }
    // stop
    $stopDate->setTimestamp(intval($_POST['stopDateCFE']));
    if ($stopDate->format('Y') !== $now->format('Y')) {
        return displayError($pug, "L'année de déclaration doit être l'année en cours");
    }
    if ($stopDate > $now) {
        return displayError($pug, "Impossible de pré-déclarer");
    }
    // start vs stop
    if ($startDate > $stopDate) {
        return displayError($pug, "Date de fin postérieure à la date de début");
    }
    if ($startDate != $stopDate && $_SESSION['enableMultiDateDeclaration'] === false) {
        return displayError($pug, "Vous n'avez pas le droit de déclarer plusieurs dates (enableMultiDateDeclaration)");
    }

    // vérification heure
    if (!is_numeric($_POST['durationHour'])) {
        return displayError($pug, "Le nombre d'heure n'est pas un nombre");
    }
    $durationHour = intval($_POST['durationHour']);
    if ($durationHour < 0 || $durationHour > 10) {
        return displayError($pug, "Le nombre d'heure doit être entre 0 et 10");
    }
    // vérification minute
    if (!is_numeric($_POST['durationMinute'])) {
        return displayError($pug, "Le nomnbre de minute n'est pas un nombre");
    }
    $durationMinute = intval($_POST['durationMinute']);
    if ($durationMinute < 0 || $durationMinute >= 60) {
        return displayError($pug, "Le nombre de minute doit être entre 0 et 10");
    }
    if ($durationHour === 0 && $durationMinute === 0) {
        return displayError($pug, "La durée ne peut être nulle");
    }

    $duration = $durationHour * 60 + $durationMinute;
    if (isset($_POST['id']) && is_numeric($_POST['id'])) {
        $id = intval($_POST['id']);
        $cfe = new CFE($conn);
        $line = $cfe->getLine($id);
        if ($line === null) {
            return displayError($pug, "Déclaration inconnue");
        }
        if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] === false) {
            // un non-admin ne peut éditer qu'une déclaration à lui et uniquement submitted
            if ($line['who'] !== $_SESSION['givavNumber']) {
                return displayError($pug, "Déclaration inconnue");
            }
            if ($line['status'] !== 'submitted') {
                return displayError($pug, "Vous ne pouvez pas éditer une déclaration validée ou rejetée");
            }
        }
        syslog(LOG_INFO, "CFE ".getClientIP()." ".$_SESSION['givavNumber']." ".$_SESSION['name']." edit declaration cfe_records.id=".$line['id']);
        syslog(LOG_INFO, "CFE ".getClientIP()." ".$_SESSION['givavNumber']." ".$_SESSION['name']." previous record: ".json_encode($line));
        $query = "UPDATE cfe_records SET registerDate = NOW(), workDate = :workDate, workType = :workType, beneficiary = :beneficiary, duration = :duration, details = :details WHERE id = :id";
        $sth = $conn->prepare($query);
        $sth->execute([ ':id' => $line['id'],
                        ':workDate' => $startDate->format('Y-m-d'),
                        ':workType' => $_POST['type'],
                        ':beneficiary' => $_POST['beneficiary'],
                        ':duration' => $duration,
                        ':details' => $_POST['details'],
        ]);
        $newLine = $cfe->getLine(intval($id));
        foreach (array_keys($newLine) as $key) {
            if (isset($line[$key]) && isset($newLine[$key]) && $line[$key] != $newLine[$key]) {
                syslog(LOG_INFO, "CFE ".getClientIP()." ".$_SESSION['givavNumber']." ".$_SESSION['name']." updated ".$key." from ".$line[$key]." to ".$newLine[$key]);
            }
        }
        return redirect("/declaration-complete");
    }

    $query ="INSERT INTO cfe_records (who, registerDate, workDate, workType, beneficiary, duration, status, details) values (:num, NOW(), :workDate, :workType, :beneficiary, :duration, 'submitted', :details)";
    $sth = $conn->prepare($query);
    $interval = DateInterval::createFromDateString('1 day');
    // DatePeriod::INCLUDE_END_DATE n'existe pas en PHP 8.1
    if (version_compare(phpversion(), '8.2', '>=')) {
        $period = new DatePeriod($startDate, $interval, $stopDate, DatePeriod::INCLUDE_END_DATE);
    } else {
        $stopDate->add($interval);
        $period = new DatePeriod($startDate, $interval, $stopDate);
    }
    foreach ($period as $dt) {
        //DEBUG echo '<pre>';
        //DEBUG var_dump($_POST);
        $sth->execute([ ':num' => $_SESSION['givavNumber'],
                        ':workDate' => $dt->format('Y-m-d'),
                        ':workType' => $_POST['type'],
                        ':beneficiary' => $_POST['beneficiary'],
                        ':duration' => $duration,
                        ':details' => $_POST['details'],
        ]);
        $sth2 = $conn->query("SELECT LAST_INSERT_ID() AS id");
        if ($sth2->rowCount() === 1) {
            $id = $sth2->fetchAll()[0]['id'];
            $cfe = new CFE($conn);
            $line = $cfe->getLine($id);
            syslog(LOG_INFO, "CFE ".getClientIP()." ".$_SESSION['givavNumber']." ".$_SESSION['name']." declare cfe_records.id=".$id." record: ".json_encode($line));
        }
    }
    redirect("/declaration-complete");
});

get('/declaration-complete', function($conn, $pug) {
    if (!isset($_SESSION['auth'])) {
        return redirect('/connexion');
    }
    $pug->displayFile('view/declaration-complete.pug', $_SESSION);
});

get('/deconnexion', function($conn) {
    if (isset($_SESSION['inSudo'])) {
        unset($_SESSION['inSudo']);
        $previousUser = Personne::load($conn, $_SESSION['previousGivavNumber']);
        unset($_SESSION['previousGivavNumber']);
        $_SESSION['givavNumber'] = $previousUser['givavNumber'];
        $_SESSION['name'] = $previousUser['name'];
        $_SESSION['mail'] = $previousUser['mail'];
        return redirect('/listeMembres');
    }
    session_destroy();
    redirect('/');
});

post('/deleteCFELine', function($conn, $pug) {
    if (!isset($_SESSION['auth'])) {
        http_response_code(500);
        echo "not authenticated";
        return;
    }
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        http_response_code(500);
        echo "id obligatoire";
        return;
    }
    $cfe = new CFE($conn);
    $line = $cfe->getLine(intval($_POST['id']));
    if ($line === null) {
        http_response_code(500);
        echo "déclaration inconnue";
        return;
    }
    if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] === false) {
        // un non-admin ne peut supprimer qu'une déclaration à lui et uniquement submitted
        if ($line['who'] !== $_SESSION['givavNumber']) {
            http_response_code(500);
            echo "déclaration inconnue";
            return;
        }
        if ($line['status'] !== 'submitted') {
            http_response_code(500);
            echo "impossible de supprimer une déclaration qui n'est plus soumise";
            return;
        }
    }
    $query = "DELETE FROM cfe_records WHERE id = :id";
    $sth = $conn->prepare($query);
    $sth->execute([ ':id' => $_POST['id'] ]);
    syslog(LOG_INFO, "CFE ".getClientIP()." ".$_SESSION['givavNumber']." ".$_SESSION['name']." delete declaration cfe_records.id=".$_POST['id'].": ".json_encode($line));
    echo "OK";
});

get('/detailsMembre', function($conn, $pug) {
    if (!isset($_SESSION['auth']) || $_SESSION['isAdmin'] === false)
        return redirect('/');
    if (!isset($_GET['numero']) || !is_numeric($_GET['numero'])) {
        return displayError($pug, "le paramètre numéro est obligatoire et doit être un entier" );
    }
    $num = intval($_GET['numero']);
    $vars = $_SESSION;
    $cfe = new CFE($conn);
    $vars['defaultCFE_TODO'] = $cfe->getDefaultCFE_TODO(getYear());
    $vars['defaultCFE_TODOHour'] = $vars['defaultCFE_TODO'] / 60;
    $vars['membre'] = Personne::load($conn, $num);
    $vars['membre']['todoHour'] = round($vars['membre']['todo'] / 60);
    if ($vars['membre']['todo'] === null || $vars['membre']['todoHour'] == $vars['defaultCFE_TODOHour'])
        $vars['membre']['todoHour'] = '';
    $lines = $cfe->getRecordsByYear($num, getYear());
    $vars['lines'] = $lines;
    $pug->displayFile('view/detailsMembre.pug', $vars);
});

post('/detailsMembreStats', function($conn, $pug) {
    if (!isset($_SESSION['auth']) || $_SESSION['isAdmin'] === false)
        return redirect('/');
    if (!isset($_POST['num']) || !is_numeric($_POST['num'])) {
        return displayError($pug, "le paramètre numéro est obligatoire et doit être un entier");
    }
    $num = intval($_POST['num']);
    $cfe = new CFE($conn);
    echo json_encode($cfe->getStats($num, getYear()));
});

get('/exportAllData', function($conn) {
    if (!isset($_SESSION['auth']) || $_SESSION['isAdmin'] === false)
        return redirect('/');
    $zipFilename = 'export-'.date('d-m-Y').'.zip';
    $zip = new ZipStream\ZipStream(
        outputName: $zipFilename,
        sendHttpHeaders: true, // enable output of HTTP headers
    );

    $years = exportAllData_getYears($conn);
    foreach ($years as $year) {
        // membres
        $data = exportAllData_getPersonnes($conn, $year);
        $zip->addFile(
            fileName: 'membres-'.$year.'.csv',
            data: $data,
        );

        // cfe
        $data = exportAllData_getRecords($conn, $year);
        if ($data !== '') {
            $zip->addFile(
                fileName: 'cfe-'.$year.'.csv',
                data: $data,
            );
        }
    }
    $zip->finish();
});

get('/importCSV', function($conn, $pug) {
    if (!isset($_SESSION['auth']))
        return redirect('/');
    $pug->displayFile('view/importCSV.pug', $_SESSION);
});

post('/importCSV', function($conn, $pug) {
    if (!isset($_FILES['csv']) || $_FILES['csv']['name'] === '') {
        http_response_code(500);
        $vars = array_merge($_SESSION, [ 'error' => "Vous avez oublié de sélectionner un fichier" ]);
        return $pug->displayFile('view/importCSV.pug', $vars);
    }
    if ($_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(500);
        $vars = array_merge($_SESSION, [ 'error' => "L'upload a échoué pour une raison inconnue" ]);
        return $pug->displayFile('view/importCSV.pug', $vars);
    }
    if ($_FILES['csv']['size'] > 100000) {
        http_response_code(500);
        $vars = array_merge($_SESSION, [ 'error' => "La taille maximale du fichier ne doit pas dépasser 100 Ko" ]);
        return $pug->displayFile('view/importCSV.pug', $vars);
    }
    if (($handle = fopen($_FILES['csv']['tmp_name'], "r")) === FALSE) {
        return displayError($pug, "Impossible d'ouvrir le fichier");
    } else {
        $data = fgetcsv($handle, 1000, ",");
        if (count($data) !== 6) {
            http_response_code(500);
            $vars = array_merge($_SESSION, [ 'error' => "Le fichier n'est pas un CSV au bon format: le séparateur de colonne doit être ,"]);
            return $pug->displayFile('view/importCSV.pug', $vars);
        }
        $vars = $_SESSION;
        rewind($handle); // on revient au début du fichier
        list($lines, $errors, $totalDuration) = parseCSV($handle, true);
        $vars['lines'] = $lines;
        $vars['errors'] = $errors;
        $vars['totalDuration'] = $totalDuration;
        rewind($handle); // on revient au début du fichier
        $contents = fread($handle, $_FILES['csv']['size']);
        $contents = trim($contents);
        $key = getSessionKey(); // private key
        $vars['sign'] = hash('sha256', $contents.$key);
        $vars['contents'] = base64_encode($contents);
        fclose($handle);
        return $pug->displayFile('view/importCSV-step2.pug', $vars);
    }
});


post('/importCSV-finish', function($conn, $pug) {
    if (!isset($_SESSION['auth']))
        return redirect('/');
    foreach ([ 'contents', 'sign' ] as $key) {
        if (!isset($_POST[$key]) || $_POST[$key] === '') {
            return displayError($pug, $key." est manquant");
        }
    }
    // on vérifie sign, est-ce que contents est le même contents que celui qui a été validé auparavant ?
    $key = getSessionKey(); // private key
    $contents = base64_decode($_POST['contents']);
    $signComputed = hash('sha256', $contents.$key);
    if ($signComputed !== $_POST['sign']) {
        return displayError($pug, "La signature fournie ne correspond pas à celle calculée, veuillez prévenir le développeur");
    }
    $handle = fopen("php://temp/maxmemory:".strlen($contents)+100, 'r+');
    fputs($handle, $contents);
    rewind($handle);
    $query = "INSERT INTO cfe_records (who, registerDate, workDate, workType, beneficiary, duration, status, details) VALUES (:who, NOW(), :workDate, :workType, :beneficiary, :duration, :status, :details)";
    $sth = $conn->prepare($query);
    list($lines, $errors, $totalDuration) = parseCSV($handle, true);
    $linesInserted = [];
    $totalDuration = 0;
    foreach ($lines as $line) {
        $status = 'submitted';
        if (intval($line['d']->format('Y')) < intval(date('Y')))
            $status = 'validated';
        $d = parseDateDDMMAAAA($line['date']);
        $d = $d['year'].'-'.$d['month'].'-'.$d['day'];
        $ret = $sth->execute([ ':who' => $_SESSION['givavNumber'],
                               ':workDate' => $d,
                               ':workType' => $line['type'],
                               ':beneficiary' => $line['beneficiary'],
                               ':duration' => $line['duration'],
                               ':status' => $status,
                               ':details' => $line['details'],
        ]);
        if ($ret === true)
            $totalDuration += $line['duration'];
        $linesInserted[] = $line;
    }
    $vars = $_SESSION;
    $vars['lines'] = $linesInserted;
    $vars['totalDuration'] = $totalDuration;
    $vars['errors'] = $errors;
    $pug->displayFile('view/importCSV-report.pug', $vars);
});

get('/importCSV-exemple', function($conn, $pug) {
    if (!isset($_SESSION['auth']))
        return redirect('/');
    header('Content-Disposition: attachment; filename="modele-import-cfe.csv"');
    header('Content-Type: text/csv');
    echo implode(',', [ 'date JJ/MM/AAAA', 'type de travaux', 'bénéficiaire', 'durée heure', 'durée minute', 'détails' ])."\r\n";
    echo implode(',', [ date('d/m/Y'), 'Atelier planeur', 'AAVO', '2', '30', 'Visite annuelle FI' ])."\r\n";
});

get('/listeDernieresCFE', function($conn, $pug) {
    if (!isset($_SESSION['auth']) || $_SESSION['isAdmin'] === false)
        return redirect('/');
    $cfe = new CFE($conn);
    $lines = $cfe->getLastRecords();
    $vars = $_SESSION;
    $vars['lines'] = $lines;
    $pug->displayFile('view/listeDernieresCFE.pug', $vars);
});

get('/listeCFE', function($conn, $pug) {
    if (!isset($_SESSION['auth']))
        return redirect('/');
    $cfe = new CFE($conn);
    $lines = $cfe->getRecords($_SESSION['givavNumber']);
    $vars = $_SESSION;
    $vars['lines'] = $lines;
    $pug->displayFile('view/listeCFE.pug', $vars);
});

get('/listeMachines', function($conn, $pug) {
    if (!isset($_SESSION['auth']) || $_SESSION['isAdmin'] === false)
        return redirect('/');
    $planeurs = new Planeurs($conn);
    $machines = $planeurs->liste();
    $vars = array_merge($_SESSION, [ 'machines' => $machines ]);
    $pug->displayFile('view/listeMachines.pug', $vars);
});

get('/listeMembres', function($conn, $pug) {
    if (!isset($_SESSION['auth']) || $_SESSION['isAdmin'] === false)
        return redirect('/');
    $membres = Personne::getAll($conn, getYear());
    $cfe = new CFE($conn);
    $defaultCFE_TODO = $cfe->getDefaultCFE_TODO(getYear());
    foreach ($membres as &$membre) {
        $membre['cfeValidated'] = $cfe->getValidated($membre['givavNumber'], getYear());
        $membre['cfeCompleted'] = $cfe->isCompleted($membre);
    }
    $vars = array_merge($_SESSION, [ 'currentUser' => $_SESSION['givavNumber'],
                                                 'inSudo' => isset($_SESSION['inSudo']),
                                                 'membres' => $membres,
                                                 'defaultCFE_TODO' => $defaultCFE_TODO,
    ]);
    $pug->displayFile('view/listeMembres.pug', $vars);
});

get('/validation', function($conn, $pug) {
    if (!isset($_SESSION['auth']) || $_SESSION['isAdmin'] === false)
        return redirect('/');
    $cfe = new CFE($conn);
    $vars = array_merge($_SESSION, [ 'lines' => $cfe->getLinesToValidate() ]);
    $pug->displayFile('view/validation.pug', $vars);
});

post('/updateMembreParams', function($conn) {
    if (!isset($_SESSION['auth']) || $_SESSION['isAdmin'] === false)
        return redirect('/');
    if (!isset($_POST['num']) || !is_numeric($_POST['num'])) {
        echo "num doit être un nombre";
        return http_response_code(500);
    }
    foreach ([ 'cfeTODO', 'enableMultiDateDeclaration' ] as $v) {
        if (!isset($_POST[$v])) {
            echo $v." est obligatoire";
            return http_response_code(500);
        }
    }
    if ($_POST['cfeTODO'] === '') {
        $query = "DELETE FROM cfe_todo WHERE who = :num";
        $sth = $conn->prepare($query);
        $sth->execute([
            ':num' => intval($_POST['num']),
        ]);
    } else {
        if (!is_numeric($_POST['cfeTODO']) ||
            intval($_POST['cfeTODO']) < 0 || intval($_POST['cfeTODO']) > 200) {
            echo "cfeTODO doit être entre 0 et 200";
            return http_response_code(500);
        }
        $query = "INSERT INTO cfe_todo (who, year, todo) VALUES (:who, :year, :todo) ON DUPLICATE KEY UPDATE todo = :todo";
        $sth = $conn->prepare($query);
        $sth->execute([
            ':who' => intval($_POST['num']),
            ':year' => getYear(),
            ':todo' => intval($_POST['cfeTODO']) * 60,
        ]);
    }
    $enableMultiDateDeclaration = 0;
    if ($_POST['enableMultiDateDeclaration'] === '1')
        $enableMultiDateDeclaration = 1;
    $query = "UPDATE personnes SET enableMultiDateDeclaration = :v WHERE givavNumber = :who";
    $sth = $conn->prepare($query);
        $sth->execute([
            ':who' => intval($_POST['num']),
            ':v' => $enableMultiDateDeclaration,
        ]);
    echo "OK";
});

post('/updateCFELine', function($conn) {
    if (!isset($_SESSION['auth']) || $_SESSION['isAdmin'] === false)
        return redirect('/');
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        echo "id doit être un nombre";
        return http_response_code(500);
    }
    if (!isset($_POST['status']) || $_POST['status'] === '') {
        echo "status est obligatoire";
        return http_response_code(500);
    }
    $query = "UPDATE cfe_records SET status = :status, statusWho = :num, statusDate = NOW(), rejectedCause = NULL WHERE id = :id";
    $vars = [
        ':id' => $_POST['id'],
        ':status' => $_POST['status'],
        ':num' => $_SESSION['givavNumber'],
    ];
    if (isset($_POST['rejectedCause']) && $_POST['rejectedCause'] !== '') {
        $query = "UPDATE cfe_records SET status = :status, statusWho = :num, statusDate = NOW(), rejectedCause = :rejectedCause WHERE id = :id";
        $vars = [
            ':id' => $_POST['id'],
            ':status' => $_POST['status'],
            ':rejectedCause' => $_POST['rejectedCause'],
            ':num' => $_SESSION['givavNumber'],
        ];
    }
    $sth = $conn->prepare($query);
    $sth->execute($vars);
    echo "OK";
});

get('/sudo', function($conn, $pug) {
    if (!isset($_SESSION['auth']) || $_SESSION['isAdmin'] === false)
        return redirect('/');
    if (isset($_SESSION['inSudo'])) {
        return displayError($pug, "Veuillez vous déconnecter avant de tenter à nouveau un sudo");
    }
    if (!isset($_GET['numero']) || !is_numeric($_GET['numero'])) {
        return displayError($pug, "Numéro attendu" );
    }
    $_SESSION['inSudo'] = true;
    $_SESSION['previousGivavNumber'] = $_SESSION['givavNumber'];
    $newUser = Personne::load($conn, $_GET['numero']);
    $_SESSION['givavNumber'] = $newUser['givavNumber'];
    $_SESSION['name'] = $newUser['name'];
    $_SESSION['mail'] = $newUser['mail'];
    return redirect('/');
});

// si on arrive là c'est qu'aucune URL n'a matchée, donc => 404
http_response_code(404);
Phug::displayFile('view/404.pug');
