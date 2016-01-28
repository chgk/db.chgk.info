<?php


require_once "DbParser.php";

$parser = new DbParser(implode('',file($argv[1])));

#$parser->getArray();
print_r($parser->getArray());