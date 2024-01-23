<?php

// from https://github.com/phprouter/main

include_once __DIR__ . '/router.php';
include_once __DIR__ . '/cfe.php';
include_once __DIR__ . '/givav.php';
include_once __DIR__ . '/vendor/autoload.php';

get('/Abandon', function() {
    if (!isset($_SESSION['auth'])) {
        redirect('/login');
    }
    Phug::displayFile('view/index.pug', $_SESSION);
});

get('/doRecord', function() {
    if (!isset($_SESSION['auth'])) {
        redirect('/login');
    }
     $query = 'INSERT into cfe_records values ("", "", "", "695", "gilles.hug@gmail.com", "Autres", 1,"", "AAVO", "2024/01/19", "Soumis",  " ")';
     $sth = $conn->query($query);
     var_dump($sth);
     Phug::displayFile('view/index.pug', $_SESSION);
});

get('/NewRec', function($conn) {
    if (!isset($_SESSION['auth'])) {
        redirect('/login');
    }
    Phug::displayFile('view/NewRec.pug', $_SESSION);
});

post('/NewRec', function($conn) {
    if (!isset($_SESSION['auth'])) {
        redirect('/login');
    }
    echo '<pre>';
    var_dump($_POST);
    $query = 'SELECT 1';
    $sth = $conn->prepare($query);
    $sth->execute([  ]);
    echo json_encode([ 'result' => true ]);
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
        redirect('/login');
    }
    $cfe = new CFE($conn, $_SESSION['givavNumber']);
    $vars = array_merge($_SESSION, $cfe->getStats());
    Phug::displayFile('view/index.pug', $vars);
});

get('/login', function() {
    Phug::displayFile('view/login.pug');
});

post('/login', function($conn) {
    $vars = [];
    if (!isset($_POST['login']) || $_POST['login'] === '') {
        $vars['error'] = "Veuillez saisir votre n°nationnal ou courriel";
	return Phug::displayFile('view/login.pug', $vars);
    }
    if (!isset($_POST['pass']) || $_POST['pass'] === '') {
        $vars['error'] = "Veuillez saisir votre mot de passe";
        return Phug::displayFile('view/login.pug', $vars);
    }
    if ($_POST['login'] === 'admin' && $_POST['pass'] === 'admin') {
        $_SESSION['login'] = $_POST['login'];
        $_SESSION['isAdmin'] = true;
        $_SESSION['auth'] = true;
        return redirect('/validator');
    }
    try {
        $user = Givav::auth($_POST['login'], $_POST['pass']);
        registerPerson($conn, $user);
        $_SESSION['login'] = $_POST['login'];
        $_SESSION['isAdmin'] = false;
        $_SESSION['auth'] = true;
        $_SESSION['givavNumber'] = $user['number'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['mail'] = $user['mail'];
        return redirect('/');
    }
    catch (Exception $e) {
        $vars['error'] = $e->getMessage();
        return Phug::displayFile('view/login.pug', $vars);
    }
    $vars['error'] = "Pilote inconnu du GIVAV";
    Phug::displayFile('view/login.pug', $vars);
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
