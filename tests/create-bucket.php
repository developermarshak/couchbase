<?php

class CreateBucketHelper{
    protected $bucketName;
    protected $config;

    /**
     * @var Couchbase\Cluster
     */
    protected $cluster;
    function __construct()
    {
        $globalConfig = require __DIR__."/config/database.php";

        $this->config = $globalConfig['connections']['couchbase'];
        $this->bucketName = $this->config['bucket'];
    }

    function init(){
        $this->cluster = $this->connection();
        $this->createBucket();
        $this->createPrimaryIndex();
    }

    function reset(){
        $this->cluster = $this->connection();
        $this->removeBucket();
    }
    protected function connection(){
        $cluster = new Couchbase\Cluster("couchbase://".$this->config["host"].":".$this->config["port"]);

        $auth = new CouchbaseAuthenticator();
        $auth->cluster($this->config["user"], $this->config["password"]);

        $cluster->authenticate($auth);

        return $cluster;
    }

    protected function createBucket(){
        $manager = $this->cluster->manager($this->config["user"], $this->config["password"]);
        $manager->createBucket($this->bucketName);

        //Wait while getting up bucket
        while(!isset($manager->listBuckets()[0]) || $manager->listBuckets()[0]['nodes'][0]['status'] != "healthy"){
            sleep(1);
        }
    }

    protected function removeBucket(){
        $manager = $this->cluster->manager($this->config["user"], $this->config["password"]);
        $manager->removeBucket($this->bucketName);
    }

    protected function createPrimaryIndex(){
        $bucket = $this->cluster->openBucket($this->bucketName);
        $bucket->manager()->createN1qlPrimaryIndex($this->bucketName."-primary-index");
    }
}

$helper = new CreateBucketHelper();
$helper->init();