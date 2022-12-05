<?php

declare(strict_types = 1);

/**
 * Caldera ORM
 * ORM implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Database;

use Caldera\Database\Adapter\PDOAdapter;

class TestAdapter extends PDOAdapter {

	/**
	 * Connect adapter
	 * @return bool
	 */
	public function connect(): bool {
		return true;
	}
}
