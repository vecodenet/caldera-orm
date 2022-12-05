<?php

declare(strict_types = 1);

/**
 * Caldera ORM
 * ORM implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Database;

use Caldera\Database\Model\AbstractMetaModel;

class TestModel extends AbstractMetaModel {

	protected static $table  = 'user';

	protected static $model  = 'User';

	protected static $fields = [
		'id',
		'name',
		'email',
		'status',
		'created',
		'modified',
	];

	protected static $update = [
		'name',
		'email',
		'status',
		'modified',
	];

	protected static $defaults = [
		'status' => 'Inactive'
	];

	protected static $meta_table = 'user_meta';

	protected static $meta_field = 'user_id';
}
