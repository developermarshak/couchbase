<?php
/**
 * @author Donii Sergii <doniysa@gmail.com>
 */

/**
 * Class FactoryTest
 *
 * @author Donii Sergii <doniysa@gmail.com>
 */
class FactoryTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        app(\Illuminate\Database\Eloquent\Factory::class)->define(User::class, function (Faker\Generator $faker) {
            $userName = 'test_user';
            return [
                'username'    => $userName,
                'email'       => $faker->email,
                'msisdn'      => $faker->phoneNumber,
                'first_name'  => $faker->firstName,
                'last_name'   => $faker->lastName,
                'password'    => $userName,
                'birthday'    => '1993-03-03',
                'api_token'   => \Illuminate\Support\Str::random(400),
                'middle_name' => 'Antonovich',
                'status'      => rand(-1, 1),
                'type'        => rand(-1, 1),
                'created_at'  => time(),
            ];
        });
    }

    /**
     * Test create model from factory
     */
    public function testCreateModel() {
        factory(User::class)->create();

        $user = User::query()->first();

        $this->assertEquals($user->username, 'test_user');
    }

    /**
     * Test create model from factory
     */
    public function testMakeModel() {
        $user = factory(User::class)->make();

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test_user', $user->username);
        $this->assertInstanceOf(\Carbon\Carbon::class, $user->birthday);
    }
}