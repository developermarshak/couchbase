<?php

$config = require __DIR__."/config/database.php";

$couchbase = $config['connections']['couchbase'];
$connection = new Couchbase\Cluster("couchbase://".$couchbase['host'].":".$couchbase['port']);

$connection->manager($couchbase["user"], $couchbase["password"])->createBucket($couchbase["bucketname"]);