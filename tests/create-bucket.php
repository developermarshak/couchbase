<?php

$config = require "./config/database.php";

$couchbase = $config['connections']['couchbase'];
$connection = new Couchbase\Cluster($couchbase['host']);

$connection->manager($couchbase["user"], $couchbase["password"])->createBucket($couchbase["bucketname"]);

echo "Couchbase bucket created!\n";