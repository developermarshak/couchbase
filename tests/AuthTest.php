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

    /**
     * Test auth attempt
     */
    public function testAuthAttempt()
    {
        $user = User::create([
            'name'     => 'John Doe',
            'email'    => 'john@doe.com',
            'password' => Hash::make('foobar'),
        ]);

        $this->assertTrue(Auth::attempt(['email' => 'john@doe.com', 'password' => 'foobar'], true));
        $this->assertTrue(Auth::check());
    }
}
