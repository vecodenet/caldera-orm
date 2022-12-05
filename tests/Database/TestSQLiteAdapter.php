<?php

declare(strict_types = 1);

/**
 * Caldera ORM
 * ORM implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Database;

use Closure;

use Caldera\Database\Adapter\SQLiteAdapter;

class TestSQLiteAdapter extends SQLiteAdapter {

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
	 * PDOStatement mock
	 * @var mixed
	 */
	protected $stmt_mock;

	/**
	 * Constructor
	 * @param mixed $stmt_mock PDOStatement mock
	 */
	public function __construct($stmt_mock) {
		$this->stmt_mock = $stmt_mock;
	}

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
		$ret = true;
		$this->query = $query;
		$this->parameters = $parameters;
		if ( preg_match('/(COUNT|SUM|MIN|MAX|AVG)\(.*\)\s+(?:AS\s+`(.*)`\s+)?FROM\s+(.*)/', $query, $matches) === 1 ) {
			$ret = [];
			$keyword = strtolower( $matches[1] );
			$alias = $matches[2] ?? null;
			$keyword = $alias ? $alias : $keyword;
			$ret[$keyword] = 1;
			$ret = (object) $ret;
		} else if ( str_starts_with($query, 'SELECT `name`, `value` FROM `user_meta`') ) {
			$ret = [
				(object) [
					'name' => 'foo',
					'value' => 'bar',
				]
			];
			$ret = $ret;
		} else if ( $query == 'SELECT * FROM `user` WHERE `id` = ?' && ($parameters[0] ?? null) == 123 ) {
			$ret = (object) [
				'id' => 123,
				'name' => 'Test',
				'email' => 'test@example.com',
				'status' => 'Active',
				'created' => '2022-11-30 15:15:15',
				'modified' => '2022-11-30 15:15:15',
			];
		} else if ( str_starts_with($query, 'SELECT * FROM `order` WHERE `id` > ?') ) {
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
			$ret = $ret;
		} else if ( str_starts_with($query, 'SELECT') ) {
			$ret = [];
		}
		if ( $callback ) {
			call_user_func($callback, $this->stmt_mock);
		}
		return $ret;
	}

	/**
	 * Get the last inserted ID
	 * @return int
	 */
	public function lastInsertId(): int {
		return 1;
	}
}
