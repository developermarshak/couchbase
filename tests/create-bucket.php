<?php

$config = require __DIR__."/config/database.php";

$couchbase = $config['connections']['couchbase'];
$connection = new Couchbase\Cluster("couchbase://".$couchbase['host'].":".$couchbase['port']);

$auth = new \Couchbase\ClassicAuthenticator();
$auth->cluster($couchbase["user"], $couchbase["password"]);


$connection->manager($couchbase["user"], $couchbase["password"]);