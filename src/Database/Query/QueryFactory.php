<?php

declare(strict_types = 1);

/**
 * Caldera ORM
 * Query builder and ORM layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Query;

use Caldera\Database\Database;
use Caldera\Database\Query\Query;

class QueryFactory {

	/**
	 * Database instance
	 * @var Database
	 */
	protected static $database;

	/**
	 * Set Database instance
	 * @param Database $database Database instance
	 */
	static function setDatabase(Database $database): void {
		static::$database = $database;
	}

	/**
	 * Build a new Query object
	 * @param  string $table Table name
	 * @param  string $alias Table alias
	 * @return Query
	 */
	static function build(string $table = '', string $alias = ''): Query {
		$query = new Query(static::$database);
		if ($table) {
			$query->table($table, $alias);
		}
		return $query;
	}
}
