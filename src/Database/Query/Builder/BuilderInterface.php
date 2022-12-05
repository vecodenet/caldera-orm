<?php

declare(strict_types = 1);

/**
 * Caldera ORM
 * Query builder and ORM layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Query\Builder;

use Caldera\Database\Query\CompiledQuery;
use Caldera\Database\Query\Blueprint;

interface BuilderInterface {

	/**
	 * Build query
	 * @param  Blueprint $blueprint Query blueprint
	 * @return CompiledQuery
	 */
	public function build(Blueprint $blueprint): CompiledQuery;
}
