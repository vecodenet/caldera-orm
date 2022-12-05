<?php

declare(strict_types = 1);

/**
 * Caldera ORM
 * Query builder and ORM layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Query\Builder;

use Caldera\Database\Database;
use Caldera\Database\Query\Argument;

abstract class AbstractBuilder implements BuilderInterface {

	/**
	 * Database instance
	 * @var Database
	 */
	protected $database;

	/**
	 * Constructor
	 * @param Database $database Database instance
	 */
	public function __construct(Database $database) {
		$this->database = $database;
	}

	/**
	 * Backtick expression
	 * @param  mixed  $expr Expression to backtick
	 * @return mixed
	 */
	protected function backtick($expr) {
		$ret = null;
		if ( is_string($expr) ) {
			$ret = $expr == '*' ? $expr : sprintf('`%s`', $expr);
		} else if ( $expr instanceof Argument ) {
			$ret = $expr->compile();
		}
		return $ret;
	}
}
