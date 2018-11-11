<?php
$db_url = 'mysqli://chgk@localhost/chgk_drupal';
$conf['memcache_key_prefix'] = 'db.chgk.info';

$conf['chgk_db'] = 'chgk';
$conf['sphinx_index'] = 'chgk';
$conf['sphinx_host'] = 'localhost';
$conf['sphinx_port'] = 9312;

$conf['chgk_api'] = 'http://db.chgk.info:SECRET_TOKEN@api.baza-voprosov.ru/';
require_once("db_settings.php");
