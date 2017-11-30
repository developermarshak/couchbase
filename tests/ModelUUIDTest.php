<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 30.11.2017
 * Time: 12:27
 */

class ModelUUIDTest extends TestCase
{
    const COLLECTION_NAME = "user_u_u_i_ds";

    const UUID_REGEQ = "[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}";

    public function tearDown()
    {
        UserUUID::truncate();
    }

    public function testNewModel()
    {
        $user = new UserUUID;
        $this->assertInstanceOf('Mpociot\Couchbase\Eloquent\Model', $user);
        $this->assertInstanceOf('Mpociot\Couchbase\Connection', $user->getConnection());
        $this->assertEquals(false, $user->exists);
        $this->assertEquals(static::COLLECTION_NAME, $user->getTable());
        $this->assertEquals(static::COLLECTION_NAME, $user->getCollectionName());
        $this->assertInstanceOf(\Mpociot\Couchbase\Eloquent\Builder::class, $user->getCollection());
        $this->assertEquals('_id', $user->getKeyName());
    }

    public function testInsert()
    {
        $user = new UserUUID;
        $user->name = 'John Doe';
        $user->title = 'admin';
        $user->age = 35;

        $result = $user->save();

        $this->assertTrue($result);
        $this->assertEquals(true, $user->exists);
        $this->assertEquals(1, UserUUID::count());

        $this->assertTrue(isset($user->_id));
        $this->assertInstanceOf('Carbon\Carbon', $user->created_at);

        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals(35, $user->age);

        return $user;
    }

    public function testUpdate()
    {
        $user = new UserUUID;
        $user->name = 'John Doe';
        $user->title = 'admin';
        $user->age = 35;
        $user->save();

        $check = UserUUID::find($user->_id);

        $check->age = 36;
        $check->save();

        $this->assertEquals(true, $check->exists);
        $this->assertInstanceOf('Carbon\Carbon', $check->created_at);
        $this->assertInstanceOf('Carbon\Carbon', $check->updated_at);
        $this->assertEquals(1, UserUUID::count());

        $this->assertEquals('John Doe', $check->name);
        $this->assertEquals(36, $check->age);

        $user->update(['age' => 20]);

        $check = UserUUID::find($user->_id);
        $this->assertEquals(20, $check->age);
    }

    public function testDelete()
    {
        UserUUID::create(["name"=>"Petr Smith"]);

        $user = new UserUUID;
        $user->name = 'John Doe';
        $user->title = 'admin';
        $user->age = 35;
        $user->save();

        $this->assertEquals(true, $user->exists);
        $this->assertEquals(2, UserUUID::count());

        $user->delete();

        $this->assertEquals(1, UserUUID::count());
    }

    function testBuilderInsert(){
        $query = User::query();
        /**
         * @var \Mpociot\Couchbase\Query\Builder $query
         */

        $id = $query->setUuidEnable(true)->insertGetId(['name' => 'John Doe', 'age' => 35, 'title' => 'admin']);

        $this->checkIdFormat($id, 'users');

        $query = User::query();

        $userGet = $query->where("_id", "=", $id)->first();

        $this->assertEquals($userGet->name, 'John Doe');
        $this->assertEquals($userGet->age, 35);
        $this->assertEquals($userGet->title, 'admin');

        return $userGet;
    }

    /**
     * @depends testBuilderInsert
     * @depends testInsert
     */
    public function testIdFormat(User $user){
        $this->checkIdFormat($user->_id, $user->getCollectionName());
    }

    /**
     * @param $id
     * @param $collectionName
     */
    protected function checkIdFormat($id, $collectionName){

        $this->assertInternalType("string", $id);

        $this->assertNotEquals('', $id);

        $this->assertNotEquals(0, strlen($id));

        $this->assertRegExp("/".$collectionName."::".static::UUID_REGEQ."/", $id);
    }
}