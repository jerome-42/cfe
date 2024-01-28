<?php

// from https://github.com/phprouter/main

include_once __DIR__ . '/router.php';
include_once __DIR__ . '/cfe.php';
include_once __DIR__ . '/givav.php';
include_once __DIR__ . '/personne.php';
include_once __DIR__ . '/vendor/autoload.php';

get('/Abandon', function() {
    if (!isset($_SESSION['auth'])) {
        redirect('/connexion');
    }
    Phug::displayFile('view/index.pug', $_SESSION);
});

post('/doRecord', function() {
    if (!isset($_SESSION['auth'])) {
        redirect('/connexion');
    }
     $query = 'INSERT into cfe_records values ("", "", "", "695", "gilles.hug@gmail.com", "Autres", 1,"", "AAVO", "2024/01/19", "Soumis",  " ")';
     $temp ='INSERT into cfe_records values ("';
     $temp_time = time();
     var_dump($temp_time);
     $sth = $conn->query($query);
     //var_dump($sth);
     Phug::displayFile('view/index.pug', $_SESSION);
});

get('/declaration', function($conn) {
    if (!isset($_SESSION['auth'])) {
        redirect('/connexion');
    }
    Phug::displayFile('view/declaration.pug', $_SESSION);
});

post('/declaration', function($conn) {
    if (!isset($_SESSION['auth'])) {
        redirect('/connexion');
    }
    // vérification que tous les éléments du formulaire ont été saisi (dateCFE, type, beneficiaire, duree)
    foreach ([ 'dateCFE', 'type', 'beneficiaire', 'duree' ] as $elem) {
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
    // vérification duree est > 0 et <= 10
    if (!is_numeric($_POST['duree'])) {
        http_response_code(500);
        return Phug::displayFile('view/error.pug', [ 'message' => "duree n'est pas un nombre" ]);
    }
    $duree = floatval($_POST['duree']);
    if ($duree <= 0 || $duree > 10) {
        http_response_code(500);
        return Phug::displayFile('view/error.pug', [ 'message' => "duree doit être entre 0 et 10" ]);
    }
    $commentaire = ''; // default comment
    if (isset($_POST['commentaires']))
        $commentaire = $_POST['commentaires'];
    //DEBUG echo '<pre>';
    //DEBUG var_dump($_POST);
    $query ='INSERT into cfe_records (NumNational, Bénéficiaire, TypeTravaux, Durée, Commentaires, DateTravaux) values (:num, :beneficiaire, :type, :duree, :commentaire, :dateCFE)';
    $sth = $conn->prepare($query);
    $sth->execute([ ':num' => $_SESSION['givavNumber'],
                    ':beneficiaire' => $_POST['beneficiaire'],
                    ':type' => $_POST['type'],
                    ':duree' => $duree,
                    ':commentaire' => $commentaire,
                    ':dateCFE' => $dateCFE->format('Y-m-d'),
    ]);
    redirect("/declaration-completee");
});

get('/declaration-completee', function() {
    if (!isset($_SESSION['auth'])) {
        redirect('/connexion');
    }
    Phug::displayFile('view/declaration-completee.pug');
});

get('/editRecords', function($conn) {
	$query="SELECT * from cfe_records WHERE NumNational = " ;
	
});

get('/test', function($conn) {
    $query = 'SELECT email FROM personnes  WHERE NumNational = "695" ; //"$conn"';  //ORDER BY name LIMIT 1';
    $sth = $conn->query($query);
    $lines = $sth->fetchAll();
    echo '<pre>';
    var_dump($lines);
    echo '</pre>';
    //echo $lines[0]['name'];
});

get('/test2', function($conn) {
    $query = 'SELECT durée Nom FROM cfe_records WHERE NumNational = "695"';
    $sth = $conn->query($query);
    $lines = $sth->fetchAll();
    //var_dump($lines);
    $query = 'SELECT SUM(Durée) from cfe_records WHERE NumNational = "695"';
//  $sth = $conn->query($query);
?>
    <img src="~/www-cfe/img/carteVoeux.svg">
    <?php 
//    $lines = $sth->fetchAll();
//    var_dump($lines);
//    echo $lines // | awk " print $1" ;
//    $duree_totale = 'echo var_dump($lines)' ;//| 'awk $1' ;
//    var_dump($duree_totale);
});

get('/test3', function($conn) {
    $line = "Bonne Année 2024";
    var_dump($line);
});

get('/Voeux2024', function($conn) {
    $line = "Bonne Année 2024";
    var_dump($line);
        Phug::displayFile('view/Voeux2024.pug');
});

get('/', function($conn) {
    if (!isset($_SESSION['auth'])) {
        redirect('/connexion');
    }
    $cfe = new CFE($conn, $_SESSION['givavNumber']);
    $vars = array_merge($_SESSION, $cfe->getStats());
    Phug::displayFile('view/index.pug', $vars);
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
    if ($_POST['login'] === 'admin' && $_POST['pass'] === 'admin') {
        $_SESSION['login'] = $_POST['login'];
        $_SESSION['isAdmin'] = true;
        $_SESSION['auth'] = true;
        return redirect('/validator');
    }
    try {
        $user = Givav::auth($_POST['login'], $_POST['pass']);
        Personne::creeOuMAJ($conn, $user);
        $_SESSION['login'] = $_POST['login'];
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

get('/logout', function() {
    session_destroy();
    redirect('/');
});

post('/db', function() {
    $queryParams = [
        'draw' => 1,
        'fromId' => 0,
        'orderBy' => 'id',
        'order' => 'asc',
        'length' => PHP_INT_MAX,
        'start' => 0,
    ];
    if ($_POST['object'] === 'character')
        $queryParams['orderBy'] = 'name';
    // intval parameters
    foreach ([ 'draw', 'length', 'start' ] as $key) {
        if (isset($_POST[$key]) && intval($_POST[$key]) >= 0)
            $queryParams[$key] = intval($_POST[$key]);
    }
    if (isset($_POST['order']) && isset($_POST['order'][0]['column'])) {
        $orderColumn = intval($_POST['order'][0]['column']);
        $queryParams['orderBy'] = $_POST['columns'][$orderColumn]['data'];
        $queryParams['order'] = $_POST['order'][0]['dir'];
    }
    if (isset($_POST['search']) && isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
        $queryParams['search'] = $_POST['search']['value'];
    }
    $db = new Db();
    $returnData = $db->list($queryParams);
    $returnData['draw'] = $queryParams['draw'];
    echo json_encode($returnData);
});

// si on arrive là c'est qu'aucune URL n'a matchée, donc => 404
http_response_code(404);
Phug::displayFile('view/404.pug');
