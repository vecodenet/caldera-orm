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
use RuntimeException;

use Caldera\Database\Query\Argument;
use Caldera\Database\Query\Query;
use Caldera\Database\Database;
use Caldera\Database\Query\Blueprint;
use Caldera\Database\Query\Fragment;

use Caldera\Tests\Database\TestAdapter;
use Caldera\Tests\Database\TestMySQLAdapter;

class QueryTest extends TestCase {

	public function testCloning() {
		/**
		 * PDOStatement mock
		 * @var Stub
		 */
		$mock = self::createStub(PDOStatement::class);
		$mock->method('fetchAll')->willReturn([]);
		$mock->method('fetch')->willReturn((object)[]);
		$adapter = new TestMySQLAdapter($mock);
		$database = new Database($adapter);
		$query = new Query($database);
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
		/**
		 * PDOStatement mock
		 * @var Stub
		 */
		$mock = self::createStub(PDOStatement::class);
		$mock->method('fetchAll')->willReturn([]);
		$mock->method('fetch')->willReturn((object)[]);
		$adapter = new TestMySQLAdapter($mock);
		$database = new Database($adapter);
		$query = new Query($database);
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

	public function testUnsupportedAdapter() {
		$adapter = new TestAdapter([]);
		$database = new Database($adapter);
		$this->expectException(RuntimeException::class);
		$query = new Query($database);
		$this->expectExceptionMessage("Unsupported adapter 'TestAdapter'");
	}
}
