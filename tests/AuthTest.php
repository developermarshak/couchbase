<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

/**
 * Class AuthTest
 * Auth test
 */
class AuthTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        User::truncate();
        DB::table('password_reminders')->truncate();
    }

    protected function createPrimaryIndex() {
        $waitTime = 0;
        $connection = $this->app->db;
        while(true){
            try {
                $connection->getBucket()->manager()->createN1qlPrimaryIndex(
                    config('database.connections.couchbase.bucket')."-primary-index"
                );
                $_ENV['created'] = true;
                break;
            }

            catch (Exception $e){
                $waitTime++;
                sleep(1);

                if (substr_count($e->getMessage(), 'already exists') !== 0) {
                    break;
                }

                if($waitTime > 10){
                    throw new Exception("Now work create primary index, wait time: ".$waitTime." seconds");
                }

            }
        }
    }

    /**
     * Test auth attempt
     */
    public function testAuthAttempt()
    {
        $this->createPrimaryIndex();

        $user = User::create([
            'name'     => 'John Doe',
            'email'    => 'john@doe.com',
            'password' => Hash::make('foobar'),
        ]);

        $this->assertTrue(Auth::attempt(['email' => 'john@doe.com', 'password' => 'foobar'], true));
        $this->assertTrue(Auth::check());
    }
}
