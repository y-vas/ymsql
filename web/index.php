<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once '../src/YMSQL.php';
use YMSQL\YMSQL;

$_ENV['VSQL_INSPECT'] = true;
$_ENV[  'DB_HOST'  ] = 'mysql_ymsql';
$_ENV['DB_USERNAME'] = 'root';
$_ENV['DB_PASSWORD'] = 'root';
$_ENV['DB_DATABASE'] = 'dbtest';

$vsql = new YMSQL();

chdir('..');

$query = file_get_contents(getcwd().'/tests/example.ym.sql');
$new_q = $vsql->query($query,[
  // 'is_invited' => 9,
  'invitations' => 'afsdfa',
],true);

?>

<textarea name="name" rows="80" cols="800"><?=$new_q?></textarea>
