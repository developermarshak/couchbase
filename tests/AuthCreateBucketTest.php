<?php
/**
 * @author Donii Sergii <doniysa@gmail.com>
 */

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Tests\Fixtures\DummyOutput;
use Illuminate\Console\Events\CommandStarting;

/**
 * Class _CreateBucketTest
 *
 * @author Donii Sergii <doniysa@gmail.com>
 */
class _Auth_CreateBucketTest extends BaseTestCase
{
    public function testServiceProviderCreateBucket() {
        $inputDefinition = new \Symfony\Component\Console\Input\InputDefinition();
        $input = new ArrayInput([], $inputDefinition);
        $output = new DummyOutput();
        $commandStarting = new CommandStarting('migrate', $input, $output);
        $listener = new \Mpociot\Couchbase\Listeners\MigrateListener();

        $listener->handle($commandStarting);

        $connection = new \Mpociot\Couchbase\Connection(config('database.connections.couchbase'));

        $this->assertEquals(
            1,
            substr_count(
                $connection->getBucketName(),
                config('database.connections.couchbase.bucket')
            )
        );
    }
}