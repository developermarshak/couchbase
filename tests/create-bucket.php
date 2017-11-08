<?php

$config = require __DIR__."/config/database.php";

$couchbase = $config['connections']['couchbase'];
$cluster = new Couchbase\Cluster("couchbase://".$couchbase["host"].":".$couchbase["port"]);

$manager = $cluster->manager("conci", "devpass");

$manager->createBucket("test-bucket");
