<?php

// from https://github.com/phprouter/main

include_once __DIR__ . '/router.php';
include_once __DIR__ . '/cfe.php';
include_once __DIR__ . '/givav.php';
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
    echo '<pre>';
    //var_dump($_POST);
    $query = 'SELECT 1';
    $sth = $conn->prepare($query);
    $sth->execute([  ]);
    echo json_encode([ 'result' => true ]);
    $temp_time = time();
         $annee = floor( $temp_time / 3600 / 24 / 365.25) +1970 ;
    $jours= floor( ( $temp_time - ($annee-1970)  * 365.25 * 24 * 3600 ) /3600/24 );
   var_dump($jours); 
	 var_dump($annee);
    $heures = floor( (($temp_time - ($annee-1970)  * 365.25 * 24 * 3600 ) - $jours * 24 *3600  ) / 3600);
    var_dump($heures);
         //$theDate = date_create()
	 //$theDate = date_create_from_format("Y-m-d", NULL);
         //var_dump($temp_time);
	 //var_dump($theDate);
     //$sth = $conn->query($query);
//     var_dump($sth);
     //var_dump($_SESSION);
     $temp ='INSERT into cfe_records values ("';
     //var_dump($temp);
     $temp = $temp . "2024-01-25" . '", "NULL", "' .  $_SESSION['name'] . '", "';
     $temp = $temp . $_SESSION['givavNumber'] .'", "';
     $temp = $temp . $_SESSION['mail'] .'", "' ;
     $temp = $temp . $_POST['type'] .'", "' ;
     $temp = $temp . $_POST['duree'] .'", "' . $_POST['commentaires'] .'", "' ;;
     $temp = $temp . $_POST['beneficiaire'] . '", "' ;
     $temp = $temp . $_POST['dateCFE'] . '", "Soumis",  " ")';
     var_dump($temp);
     //$temp = $_POST['beneficiaire'];
     //var_dump($temp);
     $query=$temp;
     $sth = $conn->query($query);
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
