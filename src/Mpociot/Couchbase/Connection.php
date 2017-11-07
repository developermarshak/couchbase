<?php namespace Mpociot\Couchbase;

use Couchbase\ClassicAuthenticator;
use Couchbase\Cluster;
use CouchbaseBucket;
use CouchbaseCluster;
use CouchbaseN1qlQuery;
use Mpociot\Couchbase\Query\Builder as QueryBuilder;

class Connection extends \Illuminate\Database\Connection
{
    /**
     * The Couchbase database handler.
     *
     * @var CouchbaseBucket
     */
    protected $bucket;

    /** @var string[] */
    protected $metrics;

    /** @var int  default consistency */
    protected $consistency = \CouchbaseN1qlQuery::REQUEST_PLUS;

    /**
     * The Couchbase connection handler.
     *
     * @var CouchbaseCluster
     */
    protected $connection;

    /**
     * @var string
     */
    protected $bucketname;

    /**
     * Create a new database connection instance.
     *
     * @param  array   $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        //Build the connection string
        $dsn = $this->getDsn($config);

        $this->connection = new Cluster($dsn);

        $auth = new ClassicAuthenticator();
        $auth->cluster($config["user"], $config["password"]);

        // Select database
        $this->bucketname = $config['bucket'];

        $this->bucket = $this->connection->openBucket($this->bucketname);//, $password);

        $this->useDefaultQueryGrammar();

        $this->useDefaultPostProcessor();

        $this->useDefaultSchemaGrammar();
    }

    /**
     * Get the default post processor instance.
     *
     * @return Query\Processor
     */
    protected function getDefaultPostProcessor()
    {
        return new Query\Processor;
    }

    /**
     * Get the used bucket name.
     *
     * @return string
     */
    public function getBucketName()
    {
        $bucketName = $this->bucketname;

        if(strpos($bucketName, "-") !== false){
            $bucketName = "`".$bucketName."`";
        }

        return $bucketName;
    }

    /**
     * Begin a fluent query against a set of docuemnt types.
     *
     * @param  string  $type
     * @return Query\Builder
     */
    public function builder($type)
    {
        $processor = $this->getPostProcessor();

        $query = new QueryBuilder($this, $processor);

        return $query->from($type);
    }

    /**
     * @return QueryBuilder
     */
    public function query()
    {
        $processor = $this->getPostProcessor();

        $query = new QueryBuilder($this, $processor);

        return $query->from(null);
    }

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return mixed
     */
    public function statement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return [];
            }

            $query = CouchbaseN1qlQuery::fromString($query);
            $query->consistency($this->consistency);
            $query->positionalParams($bindings);

            return $this->executeQuery($query);
        });
    }

    /**
     * @param CouchbaseN1qlQuery $query
     *
     * @return mixed
     */
    protected function executeQuery(CouchbaseN1qlQuery $query)
    {
        return $this->bucket->query($query);
    }

    /**
     * {@inheritdoc}
     */
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return [];
            }

            $query = CouchbaseN1qlQuery::fromString($query);
            $query->consistency($this->consistency);
            $query->positionalParams($bindings);

            $result = $this->executeQuery($query);
            $rows = [];
            if (isset($result->rows)) {
                $rows = json_decode(json_encode($result->rows), true);
            }
            return $rows;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function selectWithMeta($query, $bindings = [], $useReadPdo = true)
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return [];
            }

            $query = CouchbaseN1qlQuery::fromString($query);
            $query->consistency($this->consistency);
            $query->positionalParams($bindings);

            $result = $this->executeQuery($query);
            if (isset($result->rows)) {
                $result->rows = json_decode(json_encode($result->rows), true);
            }
            return $result;
        });
    }

    /**
     * @param string $query
     * @param array  $bindings
     *
     * @return int|mixed
     */
    public function insert($query, $bindings = [])
    {
        return $this->positionalStatement($query, $bindings);
    }

    /**
     * Run an update statement against the database.
     *
     * @param string $query
     * @param array  $bindings
     *
     * @return int|\stdClass
     */
    public function update($query, $bindings = [])
    {
        return $this->positionalStatement($query, $bindings);
    }

    /**
     * Run a delete statement against the database.
     *
     * @param string $query
     * @param array  $bindings
     *
     * @return int|\stdClass
     */
    public function delete($query, $bindings = [])
    {
        return $this->positionalStatement($query, $bindings);
    }

    /**
     * {@inheritdoc}
     */
    public function affectingStatement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return 0;
            }
            $query = \CouchbaseN1qlQuery::fromString($query);
            $query->consistency($this->consistency);
            $query->namedParams(['parameters' => $bindings]);
            $result = $this->executeQuery($query);
            $this->metrics = (isset($result->metrics)) ? $result->metrics : [];

            return (isset($result->rows[0])) ? $result->rows[0] : false;
        });
    }

    /**
     * @param       $query
     * @param array $bindings
     *
     * @return mixed
     */
    public function positionalStatement($query, array $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return 0;
            }
            $query = CouchbaseN1qlQuery::fromString($query);
            $query->consistency($this->consistency);
            $query->positionalParams($bindings);
            $result = $this->executeQuery($query);
            $this->metrics = (isset($result->metrics)) ? $result->metrics : [];

            return (isset($result->rows[0])) ? $result->rows[0] : false;
        });
    }

    /**
     * Begin a fluent query against documents with given type.
     *
     * @param  string  $table
     * @return Query\Builder
     */
    public function type($table)
    {
        return $this->builder($table);
    }

    /**
     * Begin a fluent query against documents with given type.
     *
     * @param  string  $table
     * @return Query\Builder
     */
    public function table($table)
    {
        return $this->builder($table);
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return Schema\Builder
     */
    public function getSchemaBuilder()
    {
        return new Schema\Builder($this);
    }

    /**
     * Get the Couchbase bucket object.
     *
     * @return \CouchbaseBucket
     */
    public function getCouchbaseBucket()
    {
        return $this->bucket;
    }

    /**
     * return CouchbaseCluster object.
     *
     * @return \CouchbaseCluster
     */
    public function getCouchbaseCluster()
    {
        return $this->connection;
    }

    /**
     * Create a new Couchbase connection.
     *
     * @param  string  $dsn
     * @param  array   $config
     * @return \CouchbaseCluster
     */
    protected function createConnection($dsn, array $config)
    {
        $cluster = new CouchbaseCluster($config['host']);
        if (!empty($config['user']) && !empty($config['password'])) {
            $cluster->authenticateAs(strval($config['user']), strval($config['password']));
        }
        return $cluster;
    }

    /**
     * Disconnect from the underlying Couchbase connection.
     */
    public function disconnect()
    {
        unset($this->connection);
    }

    /**
     * Create a DSN string from a configuration.
     *
     * @param  array   $config
     * @return string
     */
    protected function getDsn(array $config)
    {
        // Check if the user passed a complete dsn to the configuration.
        if (! empty($config['dsn'])) {
            return $config['dsn'];
        }

        // Treat host option as array of hosts
        $hosts = is_array($config['host']) ? $config['host'] : [$config['host']];

        foreach ($hosts as &$host) {
            // Check if we need to add a port to the host
            if (strpos($host, ':') === false && ! empty($config['port'])) {
                $host = $host . ':' . $config['port'];
            }
        }

        return 'couchbase://' . implode(',', $hosts);
    }

    /**
     * Get the elapsed time since a given starting point.
     *
     * @param  int    $start
     * @return float
     */
    public function getElapsedTime($start)
    {
        return parent::getElapsedTime($start);
    }

    /**
     * Get the PDO driver name.
     *
     * @return string
     */
    public function getDriverName()
    {
        return 'couchbase';
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return Schema\Grammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return new Schema\Grammar;
    }

    /**
     * Dynamically pass methods to the connection.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->bucket, $method], $parameters);
    }
}
