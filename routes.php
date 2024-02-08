<?php

// from https://github.com/phprouter/main

include_once __DIR__ . '/router.php';
include_once __DIR__ . '/cfe.php';
include_once __DIR__ . '/givav.php';
include_once __DIR__ . '/personne.php';
include_once __DIR__ . '/vendor/autoload.php';

get('/', function($conn, $pug) {
    if (!isset($_SESSION['auth'])) {
        return redirect('/connexion');
    }
    $cfe = new CFE($conn);
    $vars = array_merge($_SESSION, $cfe->getStats($_SESSION['givavNumber'], getYear()));
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
        syslog(LOG_INFO, getClientIP()." ".$user['number']." ".$user['name']." logged");
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
            return $pug->displayFile('view/error.pug', [ 'message' => "Déclaration inconnue" ]);
        }
        if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] === false) {
            // un non-admin ne peut éditer qu'une déclaration à lui et uniquement submitted
            if ($line['who'] !== $_SESSION['givavNumber']) {
                return $pug->displayFile('view/error.pug', [ 'message' => "Déclaration inconnue" ]);
            }
            if ($line['status'] !== 'submitted') {
                return $pug->displayFile('view/error.pug', [ 'message' => "Vous ne pouvez pas éditer une déclaration validée ou rejetée" ]);
            }
        } else {
            $vars['personne'] = Personne::load($conn, $line['who']);
        }
        $line['durationHour'] = round($line['duration'] / 60);
        $line['durationMinute'] = round($line['duration'] % 60);
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
            http_response_code(500);
            return $pug->displayFile('view/error.pug', [ 'message' => $elem." n'est pas présent dans la requête" ]);
        }
    }
    $startDate = new DateTime();
    $stopDate = new DateTime();
    $now = new DateTime();
    // start
    $startDate->setTimestamp(intval($_POST['startDateCFE']));
    if ($startDate->format('Y') !== $now->format('Y')) {
        http_response_code(500);
        return $pug->displayFile('view/error.pug', [ 'message' => "L'année de déclaration doit être l'année en cours" ]);
    }
    if ($startDate > $now) {
        http_response_code(500);
        return $pug->displayFile('view/error.pug', [ 'message' => "Impossible de pré-déclarer" ]);
    }
    // stop
    $stopDate->setTimestamp(intval($_POST['stopDateCFE']));
    if ($stopDate->format('Y') !== $now->format('Y')) {
        http_response_code(500);
        return $pug->displayFile('view/error.pug', [ 'message' => "L'année de déclaration doit être l'année en cours" ]);
    }
    if ($stopDate > $now) {
        http_response_code(500);
        return $pug->displayFile('view/error.pug', [ 'message' => "Impossible de pré-déclarer" ]);
    }
    // start vs stop
    if ($startDate > $stopDate) {
        http_response_code(500);
        return $pug->displayFile('view/error.pug', [ 'message' => "Date de fin postérieure à la date de début" ]);
    }
    if ($startDate != $stopDate && $_SESSION['enableMultiDateDeclaration'] === false) {
        http_response_code(500);
        return $pug->displayFile('view/error.pug', [ 'message' => "Vous n'avez pas le droit de déclarer plusieurs dates (enableMultiDateDeclaration)" ]);
    }

    // vérification heure
    if (!is_numeric($_POST['durationHour'])) {
        http_response_code(500);
        return $pug->displayFile('view/error.pug', [ 'message' => "Le nomnbre d'heure n'est pas un nombre" ]);
    }
    $durationHour = intval($_POST['durationHour']);
    if ($durationHour < 0 || $durationHour > 10) {
        http_response_code(500);
        return $pug->displayFile('view/error.pug', [ 'message' => "Le nombre d'heure doit être entre 0 et 10" ]);
    }
    // vérification minute
    if (!is_numeric($_POST['durationMinute'])) {
        http_response_code(500);
        return $pug->displayFile('view/error.pug', [ 'message' => "Le nomnbre de minute n'est pas un nombre" ]);
    }
    $durationMinute = intval($_POST['durationMinute']);
    if ($durationMinute < 0 || $durationMinute >= 60) {
        http_response_code(500);
        return $pug->displayFile('view/error.pug', [ 'message' => "Le nombre de minute doit être entre 0 et 10" ]);
    }
    if ($durationHour === 0 && $durationMinute === 0) {
        http_response_code(500);
        return $pug->displayFile('view/error.pug', [ 'message' => "La durée ne peut être nulle" ]);
    }

    $duration = $durationHour * 60 + $durationMinute;
    if (isset($_POST['id']) && is_numeric($_POST['id'])) {
        $cfe = new CFE($conn);
        $line = $cfe->getLine(intval($_POST['id']));
        if ($line === null) {
            return $pug->displayFile('view/error.pug', [ 'message' => "Déclaration inconnue" ]);
        }
        if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] === false) {
            // un non-admin ne peut éditer qu'une déclaration à lui et uniquement submitted
            if ($line['who'] !== $_SESSION['givavNumber']) {
                return $pug->displayFile('view/error.pug', [ 'message' => "Déclaration inconnue" ]);
            }
            if ($line['status'] !== 'submitted') {
                return $pug->displayFile('view/error.pug', [ 'message' => "Vous ne pouvez pas éditer une déclaration validée ou rejetée" ]);
            }
        }
        $query = "UPDATE cfe_records SET registerDate = NOW(), workDate = :workDate, workType = :workType, beneficiary = :beneficiary, duration = :duration, details = :details WHERE id = :id";
        $sth = $conn->prepare($query);
        $sth->execute([ ':id' => $line['id'],
                        ':workDate' => $startDate->format('Y-m-d'),
                        ':workType' => $_POST['type'],
                        ':beneficiary' => $_POST['beneficiary'],
                        ':duration' => $duration,
                        ':details' => $_POST['details'],
        ]);
        syslog(LOG_INFO, getClientIP()." ".$_SESSION['givavNumber']." ".$_SESSION['name']." edit declaration cfe_records.id=".$line['id']);
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
            syslog(LOG_INFO, getClientIP()." ".$_SESSION['givavNumber']." ".$_SESSION['name']." declare cfe_records.id=".$id);
        }
    }
    redirect("/declaration-complete");
});

get('/declaration-complete', function($conn, $pug) {
    if (!isset($_SESSION['auth'])) {
        return redirect('/connexion');
    }
    $pug->displayFile('view/declaration-complete.pug');
});

get('/deconnexion', function($conn) {
    if (isset($_SESSION['inSudo'])) {
        unset($_SESSION['inSudo']);
        $previousUser = Personne::load($conn, $_SESSION['previousGivavNumber']);
        unset($_SESSION['previousGivavNumber']);
        $_SESSION['givavNumber'] = $previousUser['givavNumber'];
        $_SESSION['name'] = $previousUser['name'];
        $_SESSION['mail'] = $previousUser['mail'];
        return redirect('/');
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
    syslog(LOG_INFO, getClientIP()." ".$_SESSION['givavNumber']." ".$_SESSION['name']." delete declaration cfe_records.id=".$_POST['id'].": ".json_encode($line));
    echo "OK";
});

get('/detailsMembre', function($conn, $pug) {
    if (!isset($_SESSION['auth']) || $_SESSION['isAdmin'] === false)
        return redirect('/');
    if (!isset($_GET['numero']) || !is_numeric($_GET['numero'])) {
        http_response_code(500);
        return $pug->displayFile('view/error.pug', [ 'message' => "le paramètre numéro est obligatoire et doit être un entier" ]);
    }
    $num = intval($_GET['numero']);
    $vars = $_SESSION;
    $vars['membre'] = Personne::load($conn, $num);
    $vars['membre']['todoHour'] = round($vars['membre']['todo'] / 60);
    $cfe = new CFE($conn);
    $vars['defaultCFE_TODO'] = $cfe->getDefaultCFE_TODO(getYear());
    $vars['defaultCFE_TODOHour'] = round($vars['defaultCFE_TODO'] / 60);
    $lines = $cfe->getRecordsByYear($num, getYear());
    $vars['lines'] = $lines;
    $pug->displayFile('view/detailsMembre.pug', $vars);
});

post('/detailsMembreStats', function($conn, $pug) {
    if (!isset($_SESSION['auth']) || $_SESSION['isAdmin'] === false)
        return redirect('/');
    if (!isset($_POST['num']) || !is_numeric($_POST['num'])) {
        http_response_code(500);
        return $pug->displayFile('view/error.pug', [ 'message' => "le paramètre numéro est obligatoire et doit être un entier" ]);
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
    // membres
    $data = exportAllData_getPersonnes($conn);
    $zip->addFile(
        fileName: 'membres.csv',
        data: $data,
    );
    // cfe
    $data = exportAllData_getRecords($conn);
    $zip->addFile(
        fileName: 'cfe.csv',
        data: $data,
    );
    $zip->finish();
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
    $pug->displayFile('view/listeMembres.pug', [ 'currentUser' => $_SESSION['givavNumber'],
                                                 'inSudo' => isset($_SESSION['inSudo']),
                                                 'membres' => $membres,
                                                 'defaultCFE_TODO' => $defaultCFE_TODO,
    ]);
});

get('/validation', function($conn, $pug) {
    if (!isset($_SESSION['auth']) || $_SESSION['isAdmin'] === false)
        return redirect('/');
    $cfe = new CFE($conn);
    $lines = $cfe->getLinesToValidate();
    $pug->displayFile('view/validation.pug', [ 'lines' => $lines ]);
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
        http_response_code(500);
        return $pug->displayFile('view/error.pug', [ 'message' => "Veuillez vous déconnecter avant de tenter à nouveau un sudo" ]);
    }
    if (!isset($_GET['numero']) || !is_numeric($_GET['numero'])) {
        http_response_code(500);
        return $pug->displayFile('view/error.pug', [ 'message' => "Numéro attendu" ]);
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
