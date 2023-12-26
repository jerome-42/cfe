<?php

// from https://github.com/phprouter/main

include_once __DIR__ . '/router.php';
include_once __DIR__ . '/givav.php';
include_once __DIR__ . '/vendor/autoload.php';

get('/Abandon', function() {
    if (!isset($_SESSION['auth'])) {
        redirect('/login');
    }
    Phug::displayFile('view/index.pug', $_SESSION);
});

//post('/Abandon', function() {
//    Phug::displayFile('view/index.pug');
//});

//post("/NewRec", function() {
//	Phug::displayFile('view/NewRec.pug');
//});

get('/NewRec', function($conn) {
    if (!isset($_SESSION['auth'])) {
        redirect('/login');
    }
    Phug::displayFile('view/NewRec.pug', $_SESSION);
});

get('/test', function($conn) {
    $query = 'SELECT * FROM remorque ORDER BY immatriculation LIMIT 1';
    $sth = $conn->query($query);
    $lines = $sth->fetchAll();
    var_dump($lines);
});

get('/', function() {
    if (!isset($_SESSION['auth'])) {
        redirect('/login');
    }
    Phug::displayFile('view/index.pug', $_SESSION);
});

get('/login', function() {
    Phug::displayFile('view/login.pug');
});

post('/login', function() {
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
        Givav::auth($_POST['login'], $_POST['pass']);
        $_SESSION['login'] = $_POST['login'];
        $_SESSION['isAdmin'] = false;
        $_SESSION['auth'] = true;
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
