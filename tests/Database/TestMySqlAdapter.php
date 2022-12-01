<?php

declare(strict_types = 1);

/**
 * Caldera Database
 * Database abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Database;

use Closure;

use Caldera\Database\Adapter\MySQLAdapter;

class TestMySqlAdapter extends MySQLAdapter {

	/**
	 * Executed query
	 * @var string
	 */
	protected $query = '';

	/**
	 * Bound parameters
	 * @var array
	 */
	protected $parameters = [];

	/**
	 * Get executed query
	 * @return string
	 */
	public function getQuery(): string {
		return $this->query;
	}

	/**
	 * Get bound parameters
	 * @return array
	 */
	public function getParameters(): array {
		return $this->parameters;
	}

	/**
	 * Connect adapter
	 * @return bool
	 */
	public function connect(): bool {
		return true;
	}

	/**
	 * Execute a query
	 * @param  string   $query      Query string
	 * @param  array    $parameters Array of parameters
	 * @param  Closure $callback   Optional callback
	 * @return mixed
	 */
	public function query(string $query, array $parameters = [], Closure $callback = null) {
		$this->query = $query;
		$this->parameters = $parameters;
		if ( preg_match('/(COUNT|SUM|MIN|MAX|AVG)/', $query, $matches) === 1 ) {
			$ret = [];
			$keyword = strtolower( $matches[1] );
			$ret[$keyword] = 1;
			return (object) $ret;
		} else if ( preg_match('/^SELECT `name`, `value` FROM `user_meta`/', $query, $matches) === 1 ) {
			$ret = [
				(object) [
					'name' => 'foo',
					'value' => 'bar',
				]
			];
			return $ret;
		} else if ( $query == 'SELECT * FROM `user` WHERE `id` = ?' && ($parameters[0] ?? null) == 123 ) {
			return (object) [
				'id' => 123,
				'name' => 'Test',
				'email' => 'test@example.com',
				'status' => 'Active',
				'created' => '2022-11-30 15:15:15',
				'modified' => '2022-11-30 15:15:15',
			];
		} else if ( preg_match('/^SELECT \* FROM `order` WHERE `id` > \?/', $query) === 1 ) {
			$id = (int) $parameters[0];
			if ($id < 50) {
				$ret = [];
				for ($i = 1; $i <= 10; $i++) {
					$ret[] = (object) [
						'id' => $id + $i,
						'items' => random_int(5, 10),
						'total' => random_int(1000, 2000),
						'status' => 'Pending',
					];
				}
			} else {
				$ret = [];
			}
			return $ret;
		} else if ( preg_match('/^SELECT/', $query, $matches) === 1 ) {
			return [];
		}
		return true;
	}

	/**
	 * Get the last inserted ID
	 * @return int
	 */
	public function lastInsertId(): int {
		return 1;
	}
}
