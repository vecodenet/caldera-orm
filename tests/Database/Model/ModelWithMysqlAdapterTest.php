<?php

declare(strict_types = 1);

/**
 * Caldera ORM
 * Query builder and ORM layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Database\Model;

use DateTime;
use RuntimeException;
use PDOStatement;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

use Caldera\Database\Database;
use Caldera\Database\Query\QueryFactory;
use Caldera\Tests\Database\TestModel;
use Caldera\Tests\Database\TestMySQLAdapter;

class ModelWithMysqlAdapterTest extends TestCase {

	/**
	 * Database adapter instance
	 * @var TestMySQLAdapter
	 */
	protected static $adapter;

	/**
	 * Database instance
	 * @var Database
	 */
	protected static $database;

	protected function setUp(): void {
		/**
		 * PDOStatement mock
		 * @var Stub
		 */
		$mock = self::createStub(PDOStatement::class);
		$mock->method('fetchAll')->willReturn([]);
		$mock->method('fetch')->willReturn((object)[]);
		self::$adapter = new TestMySQLAdapter($mock);
		self::$database = new Database(self::$adapter);
		QueryFactory::setDatabase(self::$database);
	}

	public function testConstructor() {
		$user = new TestModel();
		$this->assertEquals(0, $user->id);
		$this->assertEquals('Inactive', $user->status);
		$this->assertEquals('user', $user->getTable());
		$this->assertEquals(['id', 'name', 'email', 'password', 'status', 'created', 'modified'], $user->getFields());
	}

	public function testGetAndSetProperties() {
		$user = new TestModel();
		$this->assertFalse( $user->hasProperty('foo') );
		$this->assertNull( $user->getProperty('foo') );
		$this->assertEquals( 'bar', $user->getProperty('foo', 'bar') );
		$user->setProperty('bar', 'baz');
		$this->assertTrue( $user->hasProperty('bar') );
		$this->assertEquals( 'baz', $user->getProperty('bar') );
        # Test mutators
        $this->assertInstanceOf(DateTime::class, $user->created);
        $user->created = new DateTime('2000-01-01');
        $this->assertEquals(new DateTime('2000-01-01'), $user->created);
        $user->modified = '2000-01-02';
        $this->assertEquals(new DateTime('2000-01-02'), $user->modified);
        $this->expectException(RuntimeException::class);
        $user->modified = new DateTime('2000-01-03');
	}

	public function testSelect() {
		# First
		TestModel::first();
		$this->assertEquals( 'SELECT * FROM `user`', self::$adapter->getQuery() );
		# First, specific columns
		TestModel::first( ['id', 'name'] );
		$this->assertEquals( 'SELECT `id`, `name` FROM `user`', self::$adapter->getQuery() );
		# GetBy
		TestModel::getBy('id', 1);
		$this->assertEquals( 'SELECT * FROM `user` WHERE `id` = ?', self::$adapter->getQuery() );
		# GetBy, specific columns
		TestModel::getBy('id', 1, ['id', 'name']);
		$this->assertEquals( 'SELECT `id`, `name` FROM `user` WHERE `id` = ?', self::$adapter->getQuery() );
		# Get
		TestModel::get(1);
		$this->assertEquals( 'SELECT * FROM `user` WHERE `id` = ?', self::$adapter->getQuery() );
		# Get, specific columns
		TestModel::get(1, ['id', 'name']);
		$this->assertEquals( 'SELECT `id`, `name` FROM `user` WHERE `id` = ?', self::$adapter->getQuery() );
		# All
		TestModel::all();
		$this->assertEquals( 'SELECT * FROM `user`', self::$adapter->getQuery() );
		# All, specific columns
		TestModel::all( ['id', 'name'] );
		$this->assertEquals( 'SELECT `id`, `name` FROM `user`', self::$adapter->getQuery() );
		# Where
		TestModel::where('id', 1)->all();
		$this->assertEquals( 'SELECT * FROM `user` WHERE `id` = ?', self::$adapter->getQuery() );
		# WhereIn
		TestModel::WhereIn('id', [1, 3, 5, 7])->all();
		$this->assertEquals( 'SELECT * FROM `user` WHERE `id` IN (?, ?, ?, ?)', self::$adapter->getQuery() );
		# WhereIn
		TestModel::WhereNotIn('id', [1, 3, 5, 7])->all();
		$this->assertEquals( 'SELECT * FROM `user` WHERE `id` NOT IN (?, ?, ?, ?)', self::$adapter->getQuery() );
		# WhereColumn
		TestModel::whereColumn('email', 'login')->all();
		$this->assertEquals( 'SELECT * FROM `user` WHERE `email` = `login`', self::$adapter->getQuery() );
	}

	public function testScalars() {
		# Count
		TestModel::count();
		$this->assertEquals( 'SELECT COUNT(`id`) AS `count` FROM `user`', self::$adapter->getQuery() );
		# Min
		TestModel::min('id');
		$this->assertEquals( 'SELECT MIN(`id`) AS `min` FROM `user`', self::$adapter->getQuery() );
		# Max
		TestModel::max('id');
		$this->assertEquals( 'SELECT MAX(`id`) AS `max` FROM `user`', self::$adapter->getQuery() );
		# Sum
		TestModel::sum('id');
		$this->assertEquals( 'SELECT SUM(`id`) AS `sum` FROM `user`', self::$adapter->getQuery() );
		# Avg
		TestModel::avg('id');
		$this->assertEquals( 'SELECT AVG(`id`) AS `avg` FROM `user`', self::$adapter->getQuery() );
	}

	public function testChunk() {
		TestModel::chunk(10, function(array $items) {});
		$this->assertEquals( 'SELECT * FROM `user` WHERE `id` > ? LIMIT 10', self::$adapter->getQuery() );
	}

	public function testDelete() {
		$test = new TestModel();
		$test->id = 123;
		$test->delete();
		$this->assertEquals( 'DELETE FROM `user` WHERE `id` = ? LIMIT 1', self::$adapter->getQuery() );
		$this->assertEquals( [123], self::$adapter->getParameters() );
	}

	public function testSave() {
		$now = date('Y-m-d H:i:s');
		# Insert
		$test = new TestModel();
		$test->name = 'Test';
		$test->email = 'test@example.com';
        $test->password = 'password';
		$test->status = 'Active';
		$test->save();
		$this->assertEquals( 'INSERT INTO `user` (`id`, `name`, `email`, `password`, `status`, `created`, `modified`) VALUES (?, ?, ?, ?, ?, ?, ?)', self::$adapter->getQuery() );
		$this->assertEquals( [0, 'Test', 'test@example.com', '5f4dcc3b5aa765d61d8327deb882cf99', 'Active', $now, '0000-00-00 00:00:00'], self::$adapter->getParameters() );
		# Update
		$test = new TestModel();
		$test->id = 123;
		$test->name = 'Test';
		$test->email = 'test@example.com';
        $test->password = 'password';
		$test->status = 'Active';
		$test->save();
		$this->assertEquals( 'UPDATE `user` SET `name` = ?, `email` = ?, `password` = ?, `status` = ?, `modified` = ? WHERE `id` = ?', self::$adapter->getQuery() );
		$this->assertEquals( ['Test', 'test@example.com', '5f4dcc3b5aa765d61d8327deb882cf99', 'Active', $now, 123], self::$adapter->getParameters() );
	}

	public function testRefresh() {
		$test = new TestModel();
		$test->id = 123;
		$test->refresh();
		$this->assertEquals( 'SELECT * FROM `user` WHERE `id` = ?', self::$adapter->getQuery() );
		$this->assertEquals( [123], self::$adapter->getParameters() );
	}

	public function testSerialize() {
		$test = new TestModel();
		$test->id = 123;
		$test->refresh();
		$json = json_encode($test);
		$this->assertEquals('{"id":123,"name":"Test","email":"test@example.com","password":"5f4dcc3b5aa765d61d8327deb882cf99","status":"Active","created":"2022-11-30 15:15:15","modified":"2022-11-30 15:15:15"}', $json);
	}

	public function testMetamodel() {
		$test = new TestModel();
		$test->id = 123;
		# Set metadata, scalar
		$test->setMetadata('foo', 'bar');
		$this->assertEquals( 'INSERT INTO `user_meta` (`id`, `user_id`, `name`, `value`) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = ?', self::$adapter->getQuery() );
		$this->assertEquals( [0, 123, 'foo', 'bar', 'bar'], self::$adapter->getParameters() );
		$test->setMetadata('foo', '');
		$this->assertEquals( 'INSERT INTO `user_meta` (`id`, `user_id`, `name`, `value`) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = ?', self::$adapter->getQuery() );
		$this->assertEquals( [0, 123, 'foo', '', ''], self::$adapter->getParameters() );
		# Set metadata, array
		$test->setMetadata('foo', ['bar', 'baz']);
		$this->assertEquals( [0, 123, 'foo', 'a:2:{i:0;s:3:"bar";i:1;s:3:"baz";}', 'a:2:{i:0;s:3:"bar";i:1;s:3:"baz";}'], self::$adapter->getParameters() );
		# Set metadata, bulk
		$test->setMetadata(['foo' => 'bar', 'bar' => [1, 2]]);
		$this->assertEquals( 'INSERT INTO `user_meta` (`id`, `user_id`, `name`, `value`) VALUES (?, ?, ?, ?), (?, ?, ?, ?) AS `row` ON DUPLICATE KEY UPDATE `value` = `row`.`value`', self::$adapter->getQuery() );
		$this->assertEquals( [0, 123, 'foo', 'bar', 0, 123, 'bar', 'a:2:{i:0;i:1;i:1;i:2;}'], self::$adapter->getParameters() );
		# Get metadata, specific
		$value = $test->getMetadata('foo', 'baz');
		$this->assertEquals( 'SELECT `value` FROM `user_meta` WHERE `user_id` = ? AND `name` = ?', self::$adapter->getQuery() );
		$this->assertEquals( [123, 'foo'], self::$adapter->getParameters() );
		$this->assertEquals( 'baz', $value );
		# Get metadata, all
		$test->getMetadata();
		$this->assertEquals( 'SELECT `name`, `value` FROM `user_meta` WHERE `user_id` = ? ORDER BY `name` ASC', self::$adapter->getQuery() );
		$this->assertEquals( [123], self::$adapter->getParameters() );
	}
}
