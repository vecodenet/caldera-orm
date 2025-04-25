<?php

declare(strict_types = 1);

/**
 * Caldera ORM
 * Query builder and ORM layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Database;

use DateTime;

use Caldera\Database\Model\AbstractMetaModel;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $status
 * @property DateTime $created
 * @property DateTime $modified
 */
class TestModel extends AbstractMetaModel {

    use HasTimestamps, HasPassword;

	protected static $table  = 'user';

	protected static $model  = 'User';

	protected static $fields = [
		'id',
		'name',
		'email',
		'password',
		'status',
		'created',
		'modified',
	];

	protected static $update = [
		'name',
		'email',
		'password',
		'status',
		'modified',
	];

	protected static $defaults = [
		'status' => 'Inactive',
	];

	protected static $meta_table = 'user_meta';

	protected static $meta_field = 'user_id';
}
