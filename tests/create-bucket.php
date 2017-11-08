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
    }

    protected function createPrimaryIndex(){
        try{
            $bucket = $this->cluster->openBucket($this->bucketName);
            $bucket->manager()->createN1qlPrimaryIndex($this->bucketName."-primary-index");
        }
        catch (\Couchbase\Exception $e){
            sleep(1);
            echo "Exception!!";
            $this->init();
        }
    }
}

$helper = new CreateBucketHelper();
$helper->init();