<?php
namespace Mpociot\Couchbase\Listeners;

use Couchbase\Exception;
use Illuminate\Console\Events\CommandStarting;

/**
 * Created by PhpStorm.
 * User: nikita
 * Date: 20.11.17
 * Time: 16:08
 */

class MigrateListener
{
    const LIMIT_SLEEP_TIME = 60;

    protected $listenCommands = [
        'migrate',
        'migrate:install',
        'migrate:reset',
        'migrate:refresh'
    ];

    /**
     * Handle the event.
     *
     * @param  CommandStarting  $event
     * @return void
     */
    public function handle(CommandStarting $event)
    {
        if(array_search($event->command, $this->listenCommands) === false){
            return ;
        }

        $config = config('database.connections.couchbase');
        $manager = $this->getManager($config);

        $bucketName = $config['bucket'];
        if(is_null($this->getBucketInfo($manager, $bucketName))){
            $this->createBucket($manager, $bucketName);
        }
    }

    /**
     * @param $config
     * @return \Couchbase\ClusterManager
     */
    protected function getManager($config){
        $cluster = new \Couchbase\Cluster("couchbase://".$config["host"].":".$config["port"]);

        $auth = new \CouchbaseAuthenticator();
        $auth->cluster($config["user"], $config["password"]);

        $cluster->authenticate($auth);

        $manager = $cluster->manager($config["user"], $config["password"]);

        return $manager;
    }

    /**
     * @param \Couchbase\ClusterManager $manager
     * @param $bucketName
     * @return null|array
     */
    protected function getBucketInfo(\Couchbase\ClusterManager $manager, $bucketName){
        $buckets = $manager->listBuckets();

        foreach ($buckets as $bucketInfo){
            if($bucketInfo['name'] == $bucketName){
                return $bucketInfo;
            }
        }

        return null;
    }

    /**
     * Create bucket and wait, when it up
     * @param \Couchbase\ClusterManager $manager
     * @param $bucketName
     * @throws Exception
     */
    protected function createBucket(\Couchbase\ClusterManager $manager, $bucketName){
        $manager->createBucket($bucketName);

        $sleepTime = 0;
        //Wait while set up bucket
        while($sleepTime++ < static::LIMIT_SLEEP_TIME){
            sleep(1);

            $bucketInfo = $this->getBucketInfo($manager, $bucketName);
            if(is_null($bucketInfo) || !$this->isBucketUp($bucketInfo)){
                continue;
            }
            return ;
        }

        throw new Exception("Not set up bucket after: ".$sleepTime." seconds");
    }

    /**
     * Check, bucket up or not
     * @param $bucketInfo
     * @return bool
     */
    protected function isBucketUp($bucketInfo){
        if(!isset($bucketInfo['nodes'])){
            return false;
        }
        if(count($bucketInfo['nodes']) < 1){
            return false;
        }
        foreach($bucketInfo['nodes'] as $nodeInfo){
            if($nodeInfo['status'] != "healthy"){
                return false;
            }
        }
        return true;
    }
}