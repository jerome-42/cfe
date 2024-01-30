<?php

// from https://github.com/phprouter/main

include_once __DIR__ . '/router.php';
include_once __DIR__ . '/cfe.php';
include_once __DIR__ . '/givav.php';
include_once __DIR__ . '/personne.php';
include_once __DIR__ . '/vendor/autoload.php';

get('/', function($conn) {
    if (!isset($_SESSION['auth'])) {
        redirect('/connexion');
    }
    $cfe = new CFE($conn, $_SESSION['givavNumber']);
    $vars = array_merge($_SESSION, $cfe->getStats());
    Phug::displayFile('view/index.pug', $vars);
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

get('/connexion', function() {
    if (isset($_SESSION['auth'])) {
        redirect('/');
    }
    Phug::displayFile('view/connexion.pug');
});

post('/connexion', function($conn) {
    $vars = [];
    if (!isset($_POST['login']) || $_POST['login'] === '') {
        $vars['error'] = "Veuillez saisir votre n°nationnal ou courriel";
	return Phug::displayFile('view/connexion.pug', $vars);
    }
    if (!isset($_POST['pass']) || $_POST['pass'] === '') {
        $vars['error'] = "Veuillez saisir votre mot de passe";
        return Phug::displayFile('view/connexion.pug', $vars);
    }
    try {
        $user = Givav::auth($_POST['login'], $_POST['pass']);
        Personne::creeOuMAJ($conn, $user);
        $_SESSION['auth'] = true;
        $_SESSION['givavNumber'] = $user['number'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['mail'] = $user['mail'];
        return redirect('/');
    }
    catch (Exception $e) {
        $vars['error'] = $e->getMessage();
        return Phug::displayFile('view/connexion.pug', $vars);
    }
    $vars['error'] = "Pilote inconnu du GIVAV";
    Phug::displayFile('view/connexion.pug', $vars);
});

get('/declaration', function($conn) {
    if (!isset($_SESSION['auth'])) {
        return redirect('/connexion');
    }
    Phug::displayFile('view/declaration.pug', $_SESSION);
});

post('/declaration', function($conn) {
    if (!isset($_SESSION['auth'])) {
        return redirect('/connexion');
    }
    // vérification que tous les éléments du formulaire ont été saisi (dateCFE, type, beneficiaire, duree)
    foreach ([ 'dateCFE', 'type', 'beneficiary', 'duration' ] as $elem) {
        if (!isset($_POST[$elem]) || $_POST[$elem] === '') {
            http_response_code(500);
            return Phug::displayFile('view/error.pug', [ 'message' => $elem." n'est pas présent dans la requête" ]);
        }
    }
    // vérification que dateCFE est entre le 1er janvier et le 31 décembre de cette année
    if (!is_numeric($_POST['dateCFE'])) {
        http_response_code(500);
        return Phug::displayFile('view/error.pug', [ 'message' => "dateCFE n'est pas un entier" ]);
    }
    $now = new DateTime();
    $dateCFE = new DateTime();
    $dateCFE->setTimestamp(intval($_POST['dateCFE']));
    if ($dateCFE->format('Y') !== $now->format('Y')) {
        http_response_code(500);
        return Phug::displayFile('view/error.pug', [ 'message' => "L'année de déclaration doit être l'année en cours" ]);
    }
    if ($dateCFE > $now) {
        http_response_code(500);
        return Phug::displayFile('view/error.pug', [ 'message' => "Impossible de pré-déclarer" ]);
    }
    // vérification duration est > 0 et <= 10
    if (!is_numeric($_POST['duration'])) {
        http_response_code(500);
        return Phug::displayFile('view/error.pug', [ 'message' => "duree n'est pas un nombre" ]);
    }
    $duration = floatval($_POST['duration']);
    if ($duration <= 0 || $duration > 10) {
        http_response_code(500);
        return Phug::displayFile('view/error.pug', [ 'message' => "duree doit être entre 0 et 10" ]);
    }
    //DEBUG echo '<pre>';
    //DEBUG var_dump($_POST);
    $query ="INSERT into cfe_records (who, registerDate, workDate, workType, beneficiary, duration, status, details) values (:num, NOW(), :workDate, :workType, :beneficiary, :duration, 'submitted', :details)";
    $sth = $conn->prepare($query);
    $sth->execute([ ':num' => $_SESSION['givavNumber'],
                    ':workDate' => $dateCFE->format('Y-m-d'),
                    ':workType' => $_POST['type'],
                    ':beneficiary' => $_POST['beneficiary'],
                    ':duration' => $duration,
                    ':details' => $_POST['details'],
    ]);
    $conn->commit();
    redirect("/declaration-complete");
});

get('/declaration-complete', function() {
    if (!isset($_SESSION['auth'])) {
        redirect('/connexion');
    }
    Phug::displayFile('view/declaration-complete.pug');
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

get('/detailsMembre', function($conn) {
    if (!isset($_SESSION['auth']) || $_SESSION['isAdmin'] === false)
        return redirect('/');
    if (!isset($_GET['numero']) || !is_numeric($_GET['numero'])) {
        http_response_code(500);
        return Phug::displayFile('view/error.pug', [ 'message' => "le paramètre numéro est obligatoire et doit être un entier" ]);
    }
    $num = intval($_GET['numero']);
    $vars = $_SESSION;
    $vars['membre'] = Personne::load($conn, $num);
    $cfe = new CFE($conn, $num);
    $vars['defaultCFE_TODO'] = $cfe->getDefaultCFE_TODO();
    $lines = $cfe->getRecords();
    $vars['lines'] = $lines;
    Phug::displayFile('view/detailsMembre.pug', $vars);
});

post('/detailsMembreStats', function($conn) {
    if (!isset($_SESSION['auth']) || $_SESSION['isAdmin'] === false)
        return redirect('/');
    if (!isset($_POST['num']) || !is_numeric($_POST['num'])) {
        http_response_code(500);
        return Phug::displayFile('view/error.pug', [ 'message' => "le paramètre numéro est obligatoire et doit être un entier" ]);
    }
    $num = intval($_POST['num']);
    $cfe = new CFE($conn, $num);
    echo json_encode($cfe->getStats());
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

get('/listeCFE', function($conn) {
    if (!isset($_SESSION['auth']))
        return redirect('/');
    $cfe = new CFE($conn, $_SESSION['givavNumber']);
    $lines = $cfe->getRecords();
    $vars = $_SESSION;
    $vars['lines'] = $lines;
    Phug::displayFile('view/listeCFE.pug', $vars);
});

get('/listeMembres', function($conn) {
    if (!isset($_SESSION['auth']) || $_SESSION['isAdmin'] === false)
        return redirect('/');
    $membres = Personne::getAll($conn);
    $cfe = new CFE($conn, null);
    $defaultCFE_TODO = $cfe->getDefaultCFE_TODO();
    Phug::displayFile('view/listeMembres.pug', [ 'currentUser' => $_SESSION['givavNumber'],
                                                 'inSudo' => isset($_SESSION['inSudo']),
                                                 'membres' => $membres,
                                                 'defaultCFE_TODO' => $defaultCFE_TODO,
    ]);
});

get('/validation', function($conn) {
    if (!isset($_SESSION['auth']) || $_SESSION['isAdmin'] === false)
        return redirect('/');
    $cfe = new CFE($conn, null);
    $lines = $cfe->getLinesToValidate();
    Phug::displayFile('view/validation.pug', [ 'lines' => $lines ]);
});

post('/updateCFE_TODO', function($conn) {
    if (!isset($_SESSION['auth']) || $_SESSION['isAdmin'] === false)
        return redirect('/');
    if (!isset($_POST['num']) || !is_numeric($_POST['num'])) {
        echo "num doit être un nombre";
        return http_response_code(500);
    }
    if (!isset($_POST['cfeTODO'])) {
        echo "cfeTODO est obligatoire";
        return http_response_code(500);
    }
    if ($_POST['cfeTODO'] === '') {
        $query = "UPDATE personnes SET cfeTODO = NULL WHERE givavNumber = :num";
        $sth = $conn->prepare($query);
        $sth->execute([
            ':num' => intval($_POST['num']),
        ]);
    } else {
        if (!is_numeric($_POST['cfeTODO']) ||
            intval($_POST['cfeTODO']) < 0 || intval($_POST['cfeTODO']) > 100) {
            echo "cfeTODO doit être entre 0 et 100";
            return http_response_code(500);
        }
        $query = "UPDATE personnes SET cfeTODO = :cfeTODO WHERE givavNumber = :num";
        $sth = $conn->prepare($query);
        $sth->execute([
            ':num' => intval($_POST['num']),
            ':cfeTODO' => intval($_POST['cfeTODO']),
        ]);
    }
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
    $query = "UPDATE cfe_records SET status = :status, statusWho = :num, statusDate = NOW() WHERE id = :id";
    $sth = $conn->prepare($query);
    $sth->execute([
        ':id' => $_POST['id'],
        ':status' => $_POST['status'],
        ':num' => $_SESSION['givavNumber'],
    ]);
    echo "OK";
});

get('/sudo', function($conn) {
    if (!isset($_SESSION['auth']) || $_SESSION['isAdmin'] === false)
        return redirect('/');
    if (isset($_SESSION['inSudo'])) {
        http_response_code(500);
        return Phug::displayFile('view/error.pug', [ 'message' => "Veuillez vous déconnecter avant de tenter à nouveau un sudo" ]);
    }
    if (!isset($_GET['numero']) || !is_numeric($_GET['numero'])) {
        http_response_code(500);
        return Phug::displayFile('view/error.pug', [ 'message' => "Numéro attendu" ]);
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
