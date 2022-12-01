<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Database\Query;

use PHPUnit\Framework\TestCase;

use Caldera\Database\Database;
use Caldera\Database\Query\Argument;
use Caldera\Database\Query\Blueprint;
use Caldera\Database\Query\Fragment;
use Caldera\Database\Query\Query;
use Caldera\Database\Query\QueryFactory;
use Caldera\Tests\Database\TestMySqlAdapter;

class QueryWithMysqlAdapterTest extends TestCase {

	/**
	 * Database adapter instance
	 * @var TestMySqlAdapter
	 */
	protected static $adapter;

	/**
	 * Database instance
	 * @var Database
	 */
	protected static $database;

	protected function setUp(): void {
		# Create database
		$options = [];
		self::$adapter = new TestMySqlAdapter($options);
		self::$database = new Database(self::$adapter);
		QueryFactory::setDatabase(self::$database);
	}

	public function testCloning() {
		$query = new Query(self::$database);
		$query->table('user')
			->where('id', 1)
			->page(1, 15)
			->all();
		$blueprint = $query->getBlueprint();
		$this->assertInstanceOf(Blueprint::class, $blueprint);
		$clone_query = clone $query;
		$clone_blueprint = $clone_query->getBlueprint();
		$this->assertNotSame($blueprint, $clone_blueprint);
	}

	public function testFragments() {
		$query = new Query(self::$database);
		$query->table('user')
			->where('id', 1)
			->where('status', 'Active')
			->all();
		$blueprint = $query->getBlueprint();
		$fragments = $blueprint->getFragments('where');
		$this->assertCount(2, $fragments);
		$this->assertContainsOnlyInstancesOf(Fragment::class, $fragments);
		$fragment = $blueprint->getFragments('where', true);
		$data = $fragment->getData();
		$this->assertEquals(1, $data['value']);
	}

	public function testArguments() {
		# Compile *
		$argument = Argument::column('*');
		$compiled = $argument->compile();
		$this->assertEquals('*', $compiled);
		# Compile table with alias
		$argument = Argument::table('foo', 'f');
		$compiled = $argument->compile();
		$this->assertEquals('`foo` AS `f`', $compiled);
		# Compile column with alias
		$argument = Argument::column('foo', 'f');
		$compiled = $argument->compile();
		$this->assertEquals('`foo` AS `f`', $compiled);
	}

	public function testBlueprint() {
		$blueprint = new Blueprint();
		# Insert two fragments
		$fragment = new Fragment(['id' => 1]);
		$blueprint->addFragment('where', $fragment);
		$fragment = new Fragment(['id' => 2]);
		$blueprint->addFragment('where', $fragment);
		# Pop the last fragment
		$fragment = $blueprint->popFragment('where');
		$this->assertEquals( ['id' => 2], $fragment->getData() );
		# Shift the first fragment
		$fragment = $blueprint->shiftFragment('where');
		$this->assertEquals( ['id' => 1], $fragment->getData() );
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
		$this->assertEquals( "TRUNCATE `order`", self::$adapter->getQuery() );
	}

	public function testChunk() {
		$iterations = 0;
		$query = new Query(self::$database);
		$query->table('order')->chunk(function(array $items) use (&$iterations) {
			$iterations++;
			if ($iterations == 5) {
				return false;
			}
		}, 10);
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
	}

	public function testToString() {
		$query = new Query(self::$database);
		$query->table('user', 'u')
			->whereNotIn('id', [1, 3, 5, 7])
			->page(1, 15);
		$this->assertEquals( 'SELECT * FROM `user` AS `u` WHERE `id` NOT IN (?, ?, ?, ?) LIMIT 0, 15', (string) $query );
	}
}
