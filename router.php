<?php

// from https://github.com/phprouter/main

require_once('lib.php');

function get($route, $fct) {
  if ($_SERVER['REQUEST_METHOD'] == 'GET') { route($route, $fct); }
}
function post($route, $fct) {
  if ($_SERVER['REQUEST_METHOD'] == 'POST') { route($route, $fct); }
}
function put($route, $fct) {
  if ($_SERVER['REQUEST_METHOD'] == 'PUT') { route($route, $fct); }
}
function patch($route, $fct) {
  if ($_SERVER['REQUEST_METHOD'] == 'PATCH') { route($route, $fct); }
}
function delete($route, $fct) {
  if ($_SERVER['REQUEST_METHOD'] == 'DELETE') { route($route, $fct); }
}
function any($route, $fct) {
    route($route, $fct);
}

function initPug() {
}

function doRoute($fct, $apiMode = false, $parameters = []) {
    // connexion mysql
    try {
        $env = new Env();
    }
    catch (Exception $e) {
        http_response_code(500);
        $vars = [ 'message' => 'Impossible de se connecter à la base de données: '.$e->getMessage() ];
        return Phug::displayFile('view/error.pug', $vars);
    }
    $pug = $env->initPug();

    try {
        if ($apiMode === false) {
            session_start();
            // on met à jour admin
            if (isset($_SESSION['auth']) && isset($_SESSION['givavNumber'])) {
                $data = Personne::load($env->mysql, $_SESSION['givavNumber']);
                Personne::setActive($env->mysql, $_SESSION['id'], getYear());
                $_SESSION['isAdmin'] = $data['isAdmin'] === 1 ? true : false;
                $_SESSION['enableMultiDateDeclaration'] = $data['enableMultiDateDeclaration'] === 1 ? true : false;
                $_SESSION['isOwnerOfGlider'] = $data['isOwnerOfGlider'];
            }
        }
        $fct($env->mysql, $pug, $env, $parameters);
        $env->mysql->commit();
    }
    catch (Exception $e) {
        $env->mysql->rollBack();
        http_response_code(500);
        $session = getClientIP();
        if (isset($_SESSION['givavNumber']))
            $session .= " ".$_SESSION['givavNumber'];
        syslog(LOG_ERR, $session." exception at ".$e->getFile().":".$e->getLine()." ".$e->getMessage());
        $stack = [];
        $rawBacktrace = debug_backtrace();
        for ($i = 0; $i < count($rawBacktrace); $i++) {
            $line = 'in '.$rawBacktrace[$i]['function'].' on '.$rawBacktrace[$i]['file'].' at line '.$rawBacktrace[$i]['line'];
            syslog(LOG_ERR, $session." ".$line);
            $stack[] = $line;
        }
        $vars = [ 'message' => "Exception at ".$e->getFile().":".$e->getLine()." ".$e->getMessage(), 'stack' => implode("\n", $stack) ];
        if ($apiMode === true)
            echo $e->getMessage();
        else
            return $pug->displayFile('view/error.pug', $vars);
    }
}

function route($route, $fct) {
  $ROOT = $_SERVER['DOCUMENT_ROOT'];
  if ($route == "/404") {
      doRoute($fct);
      exit();
  }
  $apiMode = false;
  if (preg_match('/^\/api\//', $route))
      $apiMode = true;
  $request_url = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
  $request_url = rtrim($request_url, '/');
  $request_url = strtok($request_url, '?');
  $route_parts = explode('/', $route);
  $request_url_parts = explode('/', $request_url);
  array_shift($route_parts);
  array_shift($request_url_parts);
  if ($route_parts[0] == '' && count($request_url_parts) == 0){
      doRoute($fct, $apiMode);
      exit();
  }
  if (count($route_parts) != count($request_url_parts)){ return; }
  $parameters = [];
  for( $__i__ = 0; $__i__ < count($route_parts); $__i__++ ){
    $route_part = $route_parts[$__i__];
    if (preg_match("/^[$]/", $route_part)) {
        $route_part = ltrim($route_part, '$');
        array_push($parameters, $request_url_parts[$__i__]);
        $$route_part=$request_url_parts[$__i__];
    }
    else if( $route_parts[$__i__] != $request_url_parts[$__i__] ){
        return;
    }
  }
  doRoute($fct, $apiMode, $parameters);
  exit; // permet de ne pas passer dans le handler qui gère le 404
}

function out($text) {
    echo htmlspecialchars($text);
}

function set_csrf() {
    if (!isset($_SESSION["csrf"])){
        $_SESSION["csrf"] = bin2hex(random_bytes(50));
    }
    echo '<input type="hidden" name="csrf" value="'.$_SESSION["csrf"].'">';
}

function is_csrf_valid() {
    if (!isset($_SESSION['csrf']) || !isset($_POST['csrf'])) {
        return false;
    }
    if ($_SESSION['csrf'] != $_POST['csrf']) {
        return false;
    }
    return true;
}
