<?php

declare(strict_types = 1);

/**
 * Caldera ORM
 * Query builder and ORM layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Model;

use Caldera\Database\Model\AbstractModel;
use Caldera\Database\Query\Argument;
use Caldera\Database\Query\QueryFactory;

abstract class AbstractMetaModel extends AbstractModel {

	/**
	 * Metadata array
	 * @var array
	 */
	protected $metadata = [];

	/**
	 * Metadata table name
	 * @var string
	 */
	protected static $meta_table = '';

	/**
	 * Metadata foreign key field
	 * @var string
	 */
	protected static $meta_field = '';

	/**
	 * Get metadata
	 * @param  string $name    Field name
	 * @param  mixed  $default Default value
	 * @return mixed
	 */
	function getMetadata(string $name = '', $default = '') {
		$ret = $default;
		$single = !empty($name);
		$ref = static::$field_primary;
		if ($single) {
			$row = QueryFactory::build(static::$meta_table)
				->column('value')
				->where(static::$meta_field, $this->$ref)
				->where('name', $name)
				->first();
			if ($row) {
				$ret = @unserialize($row->value) ?: $row->value;
			}
		} else {
			$rows = QueryFactory::build(static::$meta_table)
				->column('name')
				->column('value')
				->where(static::$meta_field, $this->$ref)
				->order('name', 'asc')
				->all();
			if ($rows) {
				$this->metadata = [];
				foreach ($rows as $row) {
					$this->metadata[$row->name] = @unserialize($row->value) ?: $row->value;
				}
			}
		}
		return $ret;
	}

	/**
	 * Set metadata
	 * @param  mixed $name  Field name
	 * @param  mixed $value Field value
	 * @return void
	 */
	function setMetadata($name = null, $value = ''): void {
		$single = !is_array($name) && $value;
		$ref = static::$field_primary;
		if ($single) {
			if ( is_array($value) || is_object($value) ) {
				$value = @serialize($value);
			}
			$insert = ['id' => 0, static::$meta_field => $this->$ref, 'name' => $name, 'value' => $value];
			QueryFactory::build(static::$meta_table)->upsert($insert, ['value' => $value]);
		} else {
			$name = $name ? $name : $this->metadata;
			if ($name) {
				$insert = [];
				foreach ($name as $key => $value) {
					if ( is_array($value) || is_object($value) ) {
						$value = @serialize($value);
					}
					$insert[] = ['id' => 0, static::$meta_field => $this->$ref, 'name' => $key, 'value' => $value];
				}
				QueryFactory::build(static::$meta_table)->upsert($insert, ['value' => Argument::column('row.value')]);
			}
		}
	}
}