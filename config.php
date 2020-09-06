<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = '127.0.0.1';
$CFG->dbname    = 'bitnami_moodle';
$CFG->dbuser    = 'bn_moodle';
$CFG->dbpass    = '87ed597c2cbef8debae9c44cbfda9a34f050d0a54369a313faa46af06b2c2fca';
/*$CFG->dbhost    = 'lvds-rds-test.ciy8dvg1fm8b.us-east-2.rds.amazonaws.com';
$CFG->dbname    = 'moodle';
$CFG->dbuser    = 'admin';
$CFG->dbpass    = 'april25th';
$CFG->prefix    = 'mdl_'; */
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => 3306,
  'dbsocket' => '',
  'dbcollation' => 'utf8mb4_general_ci',
);

if (empty($_SERVER['HTTP_HOST'])) {
  $_SERVER['HTTP_HOST'] = '127.0.0.1:80';
}
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
  $CFG->wwwroot   = 'https://' . $_SERVER['HTTP_HOST'];
} else {
  $CFG->wwwroot   = 'http://' . $_SERVER['HTTP_HOST'];
}

//$CFG->wwwroot   = 'http://lvdsdev.iprimed.com';

//$CFG->wwwroot   = 'https://iprimedlvds.com';

//$CFG->wwwroot   = 'https://' . $_SERVER['HTTP_HOST'];

$CFG->dataroot  = '/bitnami/moodledata';
//$CFG->dataroot  = '/bitnami/efs-mount-point/moodledata';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 02775;
$CFG->localcachedir = '/var/local/cache';
//$CFG->sslproxy  = 1;



require_once(__DIR__ . '/lib/setup.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
