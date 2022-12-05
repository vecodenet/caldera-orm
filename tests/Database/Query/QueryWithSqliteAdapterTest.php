<?php

declare(strict_types = 1);

/**
 * Caldera ORM
 * ORM implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Database\Query;

use PHPUnit\Framework\TestCase;

use PDOStatement;

use Caldera\Database\Database;
use Caldera\Database\Query\Argument;
use Caldera\Database\Query\Query;
use Caldera\Database\Query\QueryFactory;
use Caldera\Tests\Database\TestModel;
use Caldera\Tests\Database\TestSQLiteAdapter;

class QueryWithSqliteAdapterTest extends TestCase {

	/**
	 * Database adapter instance
	 * @var TestSQLiteAdapter
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
		self::$adapter = new TestSQLiteAdapter($mock);
		self::$database = new Database(self::$adapter);
		QueryFactory::setDatabase(self::$database);
	}

	public function testSelectWithSimpleWhere() {
		$query = new Query(self::$database);
		$query->table('user')
			->where('id', 1)
			->first();
		$this->assertEquals( "SELECT * FROM `user` WHERE `id` = ?", self::$adapter->getQuery() );
		$this->assertEquals( [1], self::$adapter->getParameters() );
	}

	public function testSelectWithCompoundWhere() {
		$query = new Query(self::$database);
		$query->table('user')
			->where(function($query) {
				$query->where('id', 1)->where('id', 2, '!=');
			})
			->where('status', 'Active')
			->order('id', 'DESC')
			->page(1, 15)
			->all();
		$this->assertEquals( "SELECT * FROM `user` WHERE (`id` = ? AND `id` != ?) AND `status` = ? ORDER BY `id` DESC LIMIT 0, 15", self::$adapter->getQuery() );
		$this->assertEquals( [1, 2, 'Active'], self::$adapter->getParameters() );
	}

	public function testSelectWithColumnWhere() {
		$query = new Query(self::$database);
		$query->table('user')
			->whereColumn('email', 'login')
			->all();
		$this->assertEquals( "SELECT * FROM `user` WHERE `email` = `login`", self::$adapter->getQuery() );
	}

	public function testSelectWithWhereIn() {
		$query = new Query(self::$database);
		$query->table('user')
			->whereIn('id', [1, 3, 5, 7])
			->page(1, 15)
			->all();
		$this->assertEquals( "SELECT * FROM `user` WHERE `id` IN (?, ?, ?, ?) LIMIT 0, 15", self::$adapter->getQuery() );
		$this->assertEquals( [1, 3, 5, 7], self::$adapter->getParameters() );
	}

	public function testSelectWithWhereNotIn() {
		$query = new Query(self::$database);
		$query->table('user', 'u')
			->whereNotIn('id', [1, 3, 5, 7])
			->page(1, 15)
			->all();
		$this->assertEquals( "SELECT * FROM `user` AS `u` WHERE `id` NOT IN (?, ?, ?, ?) LIMIT 0, 15", self::$adapter->getQuery() );
		$this->assertEquals( [1, 3, 5, 7], self::$adapter->getParameters() );
	}

	public function testSelectWithHavingAndGroup() {
		$query = new Query(self::$database);
		$query->table('order')
			->column('id')
			->column(Argument::method('SUM', 'total'), 'total')
			->column(Argument::raw('SUM(`items`)'), 'items')
			->group('id')
			->having('total', 1000, '>')
			->havingColumn('total', 'items', '>')
			->all();
		$this->assertEquals( "SELECT `id`, SUM(`total`) AS `total`, SUM(`items`) AS `items` FROM `order` GROUP BY `id` HAVING `total` > ? AND `total` > `items`", self::$adapter->getQuery() );
		$this->assertEquals( [1000], self::$adapter->getParameters() );
		#
		$query = new Query(self::$database);
		$query->table('order')
			->column('id')
			->column(Argument::method('SUM', 'total'), 'total')
			->column(Argument::raw('SUM(`items`)'), 'items')
			->group('id')
			->having(function($query) {
				$query->having('total', 1000, '>')->havingColumn('total', 'items', '>');
			})
			->all();
		$this->assertEquals( "SELECT `id`, SUM(`total`) AS `total`, SUM(`items`) AS `items` FROM `order` GROUP BY `id` HAVING (`total` > ? AND `total` > `items`)", self::$adapter->getQuery() );
		$this->assertEquals( [1000], self::$adapter->getParameters() );
	}

	public function testSelectWithUnion() {
		$first = new Query(self::$database);
		$first->table('user')
			->column('id')
			->column('name')
			->where('id', 1);
		$second = new Query(self::$database);
		$second->table('order')
			->column('id')
			->column('name')
			->where('id', 2)
			->union($first)
			->all();
		$this->assertEquals( "SELECT `id`, `name` FROM `order` WHERE `id` = ? UNION SELECT `id`, `name` FROM `user` WHERE `id` = ?", self::$adapter->getQuery() );
		$this->assertEquals( [2, 1], self::$adapter->getParameters() );
	}

	public function testSelectWithJoin() {
		$query = new Query(self::$database);
		$query->table('user', 'u')
			->join('user_meta', 'id_user', 'u.id')
			->where('u.id', 1)
			->all();
		$this->assertEquals( "SELECT * FROM `user` AS `u` INNER JOIN `user_meta` ON `id_user` = `u`.`id` WHERE `u`.`id` = ?", self::$adapter->getQuery() );
		$this->assertEquals( [1], self::$adapter->getParameters() );
		#
		$query = new Query(self::$database);
		$query->table('user', 'u')
			->join(Argument::table('user_meta', 'um'), function($query) {
				$query->where(Argument::column('um.id_user'), Argument::column('t.id'));
				$query->where(Argument::column('um.name'), 'comment');
			})
			->where('u.id', 1)
			->all();
		$this->assertEquals( "SELECT * FROM `user` AS `u` INNER JOIN `user_meta` AS `um` ON `um`.`id_user` = `t`.`id` AND `um`.`name` = ? WHERE `u`.`id` = ?", self::$adapter->getQuery() );
		$this->assertEquals( ['comment', 1], self::$adapter->getParameters() );
	}

	public function testSelectWithModel() {
		$query = new Query(self::$database);
		$query->table('user')->setModel(TestModel::class)->first();
		$this->assertEquals( "SELECT * FROM `user`", self::$adapter->getQuery() );
	}

	public function testScalarCount() {
		$query = new Query(self::$database);
		$query->table('order')->count('id');
		$this->assertEquals( "SELECT COUNT(`id`) AS `count` FROM `order`", self::$adapter->getQuery() );
	}

	public function testScalarSum() {
		$query = new Query(self::$database);
		$query->table('order')->sum('total');
		$this->assertEquals( "SELECT SUM(`total`) AS `sum` FROM `order`", self::$adapter->getQuery() );
	}

	public function testScalarMin() {
		$query = new Query(self::$database);
		$query->table('order')->min('total');
		$this->assertEquals( "SELECT MIN(`total`) AS `min` FROM `order`", self::$adapter->getQuery() );
	}

	public function testScalarMax() {
		$query = new Query(self::$database);
		$query->table('order')->max('total');
		$this->assertEquals( "SELECT MAX(`total`) AS `max` FROM `order`", self::$adapter->getQuery() );
	}

	public function testScalarAvg() {
		$query = new Query(self::$database);
		$query->table('order')->avg('total');
		$this->assertEquals( "SELECT AVG(`total`) AS `avg` FROM `order`", self::$adapter->getQuery() );
	}

	public function testTruncate() {
		$query = new Query(self::$database);
		$query->table('order')->truncate();
		$this->assertEquals( "DELETE FROM `order`", self::$adapter->getQuery() );
	}

	public function testChunk() {
		$iterations = 0;
		$query = new Query(self::$database);
		$query->table('order')->chunk(10, function(array $items) use (&$iterations) {
			$iterations++;
			if ($iterations == 5) {
				return false;
			}
		});
		$this->assertEquals( 'SELECT * FROM `order` WHERE `id` > ? LIMIT 10', self::$adapter->getQuery() );
	}

	public function testDump() {
		$query = new Query(self::$database);
		$query->table('user', 'u')
			->whereNotIn('id', [1, 3, 5, 7])
			->page(1, 15);
		ob_start();
		$query->dump();
		$output = ob_get_clean();
		$check = "SELECT * FROM `user` AS `u` WHERE `id` NOT IN (?, ?, ?, ?) LIMIT 0, 15\n".
			"array (\n".
			"  0 => 1,\n".
			"  1 => 3,\n".
			"  2 => 5,\n".
			"  3 => 7,\n".
			')';
		$this->assertEquals( $check, $output );
		#
		ob_start();
		$query->dump(true);
		$output = ob_get_clean();
	}

	public function testToString() {
		$query = new Query(self::$database);
		$query->table('user', 'u')
			->whereNotIn('id', [1, 3, 5, 7])
			->page(1, 15);
		$this->assertEquals( 'SELECT * FROM `user` AS `u` WHERE `id` NOT IN (?, ?, ?, ?) LIMIT 0, 15', (string) $query );
	}

	public function testInsert() {
		$query = new Query(self::$database);
		$query->table('user')->insert([
			'id' => 0,
			'name' => 'Test',
			'email' => 'test@example.com',
			'status' => 'Active',
			'created' => Argument::method('NOW'),
		]);
		$this->assertEquals( 'INSERT INTO `user` (`id`, `name`, `email`, `status`, `created`) VALUES (?, ?, ?, ?, NOW())', self::$adapter->getQuery() );
		$this->assertEquals( [0, 'Test', 'test@example.com', 'Active'], self::$adapter->getParameters() );
		#
		$query = new Query(self::$database);
		$query->table('user')->insert([
			[
				'id' => 0,
				'name' => 'Test',
				'email' => 'test@example.com',
				'status' => 'Active',
				'created' => Argument::method('NOW'),
			], [
				'id' => 0,
				'name' => 'Another',
				'email' => 'another@example.com',
				'status' => 'Active',
				'created' => Argument::method('NOW'),
			]
		]);
		$this->assertEquals( 'INSERT INTO `user` (`id`, `name`, `email`, `status`, `created`) VALUES (?, ?, ?, ?, NOW()), (?, ?, ?, ?, NOW())', self::$adapter->getQuery() );
		$this->assertEquals( [0, 'Test', 'test@example.com', 'Active', 0, 'Another', 'another@example.com', 'Active'], self::$adapter->getParameters() );
	}

	public function testUpdate() {
		$query = new Query(self::$database);
		$query->table('user')->where('id', 123)->update([
			'name' => 'Test',
			'email' => 'test@example.com',
			'status' => 'Active',
			'modified' => Argument::method('NOW'),
		]);
		$this->assertEquals( 'UPDATE `user` SET `name` = ?, `email` = ?, `status` = ?, `modified` = NOW() WHERE `id` = ?', self::$adapter->getQuery() );
		$this->assertEquals( ['Test', 'test@example.com', 'Active', 123], self::$adapter->getParameters() );
	}

	public function testUpsert() {
		$query = new Query(self::$database);
		$query->table('user')->upsert([
			'id' => 0,
			'name' => 'Test',
			'email' => 'test@example.com',
			'status' => 'Active',
			'created' => Argument::method('NOW'),
		], [
			'name' => 'Test',
			'modified' => Argument::method('NOW'),
		], ['email']);
		$this->assertEquals( 'INSERT INTO `user` (`id`, `name`, `email`, `status`, `created`) VALUES (?, ?, ?, ?, NOW()) ON CONFLICT (`email`) DO UPDATE SET `name` = ?, `modified` = NOW()', self::$adapter->getQuery() );
		$this->assertEquals( [0, 'Test', 'test@example.com', 'Active', 'Test'], self::$adapter->getParameters() );
	}

	public function testDelete() {
		$query = new Query(self::$database);
		$query->table('user')->where('id', 123)->delete();
		$this->assertEquals( 'DELETE FROM `user` WHERE `id` = ?', self::$adapter->getQuery() );
		$this->assertEquals( [123], self::$adapter->getParameters() );
	}
}
