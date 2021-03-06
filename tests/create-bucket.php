<?php

class CreateBucketHelper{
    protected $bucketName;
    protected $config;

    const LIMIT_SLEEP_TIME = 60;
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

        $sleepTime = 0;
        //Wait while set up bucket
        while(true){
            sleep(1);

            $sleepTime++;

            echo "Wait bucket: ".$sleepTime."\n";

            if($sleepTime > static::LIMIT_SLEEP_TIME){
                throw new Exception("Not set up bucket after: ".$sleepTime." seconds");
            }

            $bucketInfo = $manager->listBuckets()[0];

            foreach($bucketInfo['nodes'] as $nodeInfo){
                if($nodeInfo['status'] != "healthy"){
                    continue 2;
                }
            }
            sleep(1);
            return ;
        }
    }

    protected function removeBucket(){
        $manager = $this->cluster->manager($this->config["user"], $this->config["password"]);
        $manager->removeBucket($this->bucketName);
    }

    protected function createPrimaryIndex(){
        $bucket = $this->cluster->openBucket($this->bucketName);

        $waitTime = 0;
        while(true){
            try {
                $bucket->manager()->createN1qlPrimaryIndex($this->bucketName . "-primary-index");
                break;
            }
            catch (Exception $e){
                $waitTime++;
                sleep(1);
                echo "Catch: ".$waitTime."\n";

                if($waitTime > static::LIMIT_SLEEP_TIME){
                    throw new Exception("Now work create primary index, wait time: ".$waitTime." seconds");
                }

            }
        }

    }
}

$helper = new CreateBucketHelper();
$helper->init();