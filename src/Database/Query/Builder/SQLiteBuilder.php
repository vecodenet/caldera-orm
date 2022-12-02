<?php

declare(strict_types = 1);

/**
 * Caldera ORM
 * ORM implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Query\Builder;

use Closure;

use Caldera\Database\Query\Builder\AbstractBuilder;
use Caldera\Database\Query\Blueprint;
use Caldera\Database\Query\CompiledQuery;

class SQLiteBuilder extends AbstractBuilder {

	/**
	 * Build query
	 * @param  Blueprint $blueprint Query blueprint
	 * @return CompiledQuery
	 */
	public function build(Blueprint $blueprint): CompiledQuery {
		$compiled = new CompiledQuery();
		return $compiled;
	}
}
