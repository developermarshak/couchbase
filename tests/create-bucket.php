<?php
$config = require __DIR__."/config/database.php";

$couchbase = $config['connections']['couchbase'];
$cluster = new Couchbase\Cluster("couchbase://172.26.0.3:8091");

$manager = $cluster->manager("conci", "devpass");

$manager->createBucket("test-bucket");
