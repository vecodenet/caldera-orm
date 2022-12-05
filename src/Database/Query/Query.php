<?php

declare(strict_types = 1);

/**
 * Caldera ORM
 * Query builder and ORM layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Query;

use PDO;
use Closure;
use RuntimeException;

use Caldera\Database\Database;
use Caldera\Database\Adapter\MySQLAdapter;
use Caldera\Database\Adapter\SQLiteAdapter;
use Caldera\Database\Query\Argument;
use Caldera\Database\Query\Fragment;
use Caldera\Database\Query\Blueprint;
use Caldera\Database\Query\CompiledQuery;
use Caldera\Database\Query\Builder\BuilderInterface;
use Caldera\Database\Query\Builder\MySQLBuilder;
use Caldera\Database\Query\Builder\SQLiteBuilder;

class Query {

	/**
	 * Model class
	 * @var string
	 */
	protected $model;

	/**
	 * Empty flag
	 * @var bool
	 */
	protected $blank = true;

	/**
	 * Database instance
	 * @var Database
	 */
	protected $database;

	/**
	 * Query Blueprint
	 * @var Blueprint
	 */
	protected $blueprint;

	/**
	 * Query Builder
	 * @var BuilderInterface
	 */
	protected $builder;

	/**
	 * Constructor
	 * @param Database $database  Database instance
	 */
	public function __construct(Database $database) {
		$this->database = $database;
		# Create the blueprint instance
		$this->blueprint = new Blueprint();
		# Now get a builder instance
		$adapter = $this->database->getAdapter();
		# Check for subclasses
		if ( $adapter instanceof MySQLAdapter ) {
			$this->builder = new MySQLBuilder($this->database);
		} else if ( $adapter instanceof SQLiteAdapter ) {
			$this->builder = new SQLiteBuilder($this->database);
		} else {
			throw new RuntimeException( sprintf( "Unsupported adapter '%s'", get_class($adapter) ) );
		}
	}

	/**
	 * Clone callback
	 * @return void
	 */
	public function __clone() {
		$this->blueprint = clone $this->blueprint;
	}

	/**
	 * Set query model
	 * @param  string $model Model class
	 * @return $this
	 */
	public function setModel(string $model) {
		$this->model = $model;
		return $this;
	}

	/**
	 * Get query Blueprint
	 * @return Blueprint
	 */
	public function getBlueprint() {
		return $this->blueprint;
	}

	/**
	 * Set query table
	 * @param  string $table Table name
	 * @param  string $alias Table alias
	 * @return $this
	 */
	public function table(string $table, string $alias = '') {
		$this->from($table, $alias);
		if ( $this->blank ) {
			$this->column('*');
		}
		return $this;
	}

	/**
	 * Add table
	 * @param  mixed $table Table name
	 * @param  string $alias Table alias
	 * @return $this
	 */
	public function from($table, string $alias = '') {
		$data = [
			'table' => is_string($table) ? Argument::table($table) : $table,
			'alias' => $alias
		];
		$fragment = new Fragment($data);
		$this->blueprint->addFragment('tables', $fragment);
		return $this;
	}

	/**
	 * Add column
	 * @param  mixed $column Column name
	 * @param  string $alias  Column alias
	 * @return $this
	 */
	public function column($column, string $alias = '') {
		$data = [
			'column' => is_string($column) && $column != '*' ? Argument::column($column) : $column,
			'alias' => $alias
		];
		$fragment = new Fragment($data);
		if ($this->blank && $column != '*') {
			$this->blueprint->resetFragment('columns');
			$this->blank = false;
		}
		if ($column == '*') {
			$this->blueprint->resetFragment('columns');
		}
		$this->blueprint->addFragment('columns', $fragment);
		return $this;
	}

	/**
	 * Add join
	 * @param  mixed  $table    Table to join
	 * @param  mixed  $first    First column
	 * @param  mixed  $second   Second column
	 * @param  string $operator Comparison operator
	 * @param  string $type     Join type
	 * @return $this
	 */
	public function join($table, $first, $second = null, string $operator = '=', string $type = 'inner') {
		$data = [
			'table' => is_string($table) ? Argument::table($table) : $table,
			'first' => is_string($first) ? Argument::table($first) : $first,
			'operator' => $operator,
			'second' => is_string($second) ? Argument::table($second) : $second,
			'type' => $type,
		];
		$fragment = new Fragment($data);
		$this->blueprint->addFragment('join', $fragment);
		return $this;
	}

	/**
	 * Add where clause
	 * @param  mixed  $column   Column
	 * @param  mixed  $value    Column value
	 * @param  string $operator Comparison operator
	 * @param  string $boolean  Boolean operator
	 * @return $this
	 */
	public function where($column, $value = null, string $operator = '=', string $boolean = 'AND') {
		$data = [
			'column' => is_string($column) ? Argument::column($column) : $column,
			'value' => $value,
			'operator' => $operator,
			'boolean' => $boolean,
		];
		$fragment = new Fragment($data);
		$this->blueprint->addFragment('where', $fragment);
		return $this;
	}

	/**
	 * Add where clause for two columns
	 * @param  mixed  $column   Column
	 * @param  mixed  $value    Column value
	 * @param  string $operator Comparison operator
	 * @param  string $boolean  Boolean operator
	 * @return $this
	 */
	public function whereColumn($column, $value, string $operator = '=', string $boolean = 'AND') {
		$this->where(Argument::column($column), Argument::column($value), $operator, $boolean);
		return $this;
	}

	/**
	 * Add where clause with an IN operator
	 * @param  mixed  $column   Column
	 * @param  array  $values   Column value
	 * @param  string $boolean  Boolean operator
	 * @return $this
	 */
	public function whereIn($column, array $values, string $boolean = 'AND') {
		$this->where(Argument::column($column), $values, 'IN', $boolean);
		return $this;
	}

	/**
	 * Add where clause with a NOT IN operator
	 * @param  mixed  $column   Column
	 * @param  array  $values   Column value
	 * @param  string $boolean  Boolean operator
	 * @return $this
	 */
	public function whereNotIn($column, array $values, string $boolean = 'AND') {
		$this->where(Argument::column($column), $values, 'NOT IN', $boolean);
		return $this;
	}

	/**
	 * Add having clause
	 * @param  mixed  $column   Column
	 * @param  mixed  $value    Column value
	 * @param  string $operator Comparison operator
	 * @param  string $boolean  Boolean operator
	 * @return $this
	 */
	public function having($column, $value = null, string $operator = '=', string $boolean = 'AND') {
		$data = [
			'column' => is_string($column) ? Argument::column($column) : $column,
			'value' => $value,
			'operator' => $operator,
			'boolean' => $boolean,
		];
		$fragment = new Fragment($data);
		$this->blueprint->addFragment('having', $fragment);
		return $this;
	}

	/**
	 * Add having clause for two columns
	 * @param  mixed  $column   Column
	 * @param  mixed  $value    Column value
	 * @param  string $operator Comparison operator
	 * @param  string $boolean  Boolean operator
	 * @return $this
	 */
	public function havingColumn($column, $value, string $operator = '=', string $boolean = 'AND') {
		$this->having(Argument::column($column), Argument::column($value), $operator, $boolean);
		return $this;
	}

	/**
	 * Add group clause
	 * @param  mixed  $column Column
	 * @return $this
	 */
	public function group($column) {
		$data = [
			'column' => is_string($column) ? Argument::column($column) : $column
		];
		$fragment = new Fragment($data);
		$this->blueprint->addFragment('group', $fragment);
		return $this;
	}

	/**
	 * Add order clause
	 * @param  mixed  $column Column
	 * @param  string $sort   Sort order
	 * @return $this
	 */
	public function order($column, string $sort = 'ASC') {
		$data = [
			'column' => is_string($column) ? Argument::column($column) : $column,
			'sort' => $sort
		];
		$fragment = new Fragment($data);
		$this->blueprint->addFragment('order', $fragment);
		return $this;
	}

	/**
	 * Add limit clause
	 * @param  int   $limit  Limit
	 * @param  int   $offset Offset
	 * @return $this
	 */
	public function limit(int $limit, int $offset = 0) {
		$data = [
			'limit' => $limit,
			'offset' => $offset
		];
		$fragment = new Fragment($data);
		$this->blueprint->resetFragment('limit')->addFragment('limit', $fragment);
		return $this;
	}

	/**
	 * Paginate
	 * @param  int   $page Page number
	 * @param  int   $show Items per page
	 * @return $this
	 */
	public function page(int $page, int $show = 100) {
		$this->limit($show * ($page - 1), $show);
		return $this;
	}

	/**
	 * Union two queries
	 * @param  Query  $query Query to union
	 * @return $this
	 */
	public function union(Query $query) {
		$data = [
			'query' => $query
		];
		$fragment = new Fragment($data);
		$this->blueprint->addFragment('union', $fragment);
		return $this;
	}

	/**
	 * Select all rows
	 * @return mixed
	 */
	public function all() {
		return $this->select(false);
	}

	/**
	 * Select single row
	 * @return mixed
	 */
	public function first() {
		return $this->select(true);
	}

	/**
	 * Select rows
	 * @param  bool  $single   Return single row
	 * @return mixed
	 */
	public function select(bool $single = false) {
		$rows = [];
		$this->blueprint->setType('select');
		# Run the query
		$compiled = $this->builder->build($this->blueprint);
		$rows = $this->database->query($compiled->getCode(), $compiled->getParameters(), function($stmt) use ($single) {
			if ($this->model) {
				$stmt->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, $this->model, [$this]);
			}
			return $single ? $stmt->fetch() : $stmt->fetchAll();
		});
		return $rows;
	}

	/**
	 * Insert rows
	 * @param  array  $insert Data to insert
	 * @return mixed
	 */
	public function insert(array $insert) {
		$ret = 0;
		$data = [
			'insert' => $insert
		];
		$fragment = new Fragment($data);
		$ret = 0;
		$this->blueprint->addFragment('insert', $fragment);
		$this->blueprint->setType('insert');
		# Run the query
		$compiled = $this->builder->build($this->blueprint);
		$this->database->query($compiled->getCode(), $compiled->getParameters());
		$ret = $this->database->lastInsertId();
		return $ret;
	}

	/**
	 * Update rows
	 * @param  array  $update Data to update
	 * @return $this
	 */
	public function update(array $update) {
		$data = [
			'update' => $update
		];
		$fragment = new Fragment($data);
		$this->blueprint->addFragment('update', $fragment);
		$this->blueprint->setType('update');
		# Run the query
		$compiled = $this->builder->build($this->blueprint);
		$this->database->query($compiled->getCode(), $compiled->getParameters());
		return $this;
	}

	/**
	 * Delete rows
	 * @return $this
	 */
	public function delete() {
		$this->blueprint->setType('delete');
		# Run the query
		$compiled = $this->builder->build($this->blueprint);
		$this->database->query($compiled->getCode(), $compiled->getParameters());
		return $this;
	}

	/**
	 * Truncate table
	 * @return $this
	 */
	public function truncate() {
		$this->blueprint->setType('truncate');
		# Run the query
		$compiled = $this->builder->build($this->blueprint);
		$this->database->query($compiled->getCode(), $compiled->getParameters());
		return $this;
	}

	/**
	 * Upsert rows
	 * @param  array  $insert Data to insert
	 * @param  array  $update Data to update on index collision
	 * @param  array  $index  Which columns to use to detect collisions
	 * @return $this
	 */
	public function upsert(array $insert, array $update, array $index = []) {
		$data = [
			'insert' => $insert,
			'update' => $update,
			'index' => $index
		];
		$fragment = new Fragment($data);
		$this->blueprint->addFragment('insert', $fragment);
		$this->blueprint->setType('upsert');
		# Run the query
		$compiled = $this->builder->build($this->blueprint);
		$this->database->query($compiled->getCode(), $compiled->getParameters());
		return $this;
	}

	/**
	 * Get row count
	 * @param  mixed $column Column
	 * @return int
	 */
	public function count($column = null): int {
		$ret = 0;
		$column = $column ? $column : '*';
		$this->column(Argument::method('COUNT', Argument::column($column)), 'count');
		$row = $this->select(true);
		if ($row) {
			$ret = $row->count ?: 0;
		}
		return (int) $ret;
	}

	/**
	 * Get minimum value
	 * @param  mixed $column Column
	 * @return mixed
	 */
	public function min($column) {
		$ret = 0;
		$this->column(Argument::method('MIN', Argument::column($column)), 'min');
		$row = $this->select(true);
		if ($row) {
			$ret = $row->min ?: 0;
		}
		return $ret;
	}

	/**
	 * Get maximum value
	 * @param  mixed $column Column
	 * @return mixed
	 */
	public function max($column) {
		$ret = 0;
		$this->column(Argument::method('MAX', Argument::column($column)), 'max');
		$row = $this->select(true);
		if ($row) {
			$ret = $row->max ?: 0;
		}
		return $ret;
	}

	/**
	 * Get average value
	 * @param  mixed $column Column
	 * @return mixed
	 */
	public function avg($column) {
		$ret = 0;
		$this->column(Argument::method('AVG', Argument::column($column)), 'avg');
		$row = $this->select(true);
		if ($row) {
			$ret = $row->avg ?: 0;
		}
		return $ret;
	}

	/**
	 * Get sum value
	 * @param  mixed $column Column
	 * @return mixed
	 */
	public function sum($column) {
		$ret = 0;
		$this->column(Argument::method('SUM', Argument::column($column)), 'sum');
		$row = $this->select(true);
		if ($row) {
			$ret = $row->sum ?: 0;
		}
		return $ret;
	}

	/**
	 * Chunk query
	 * @param  int     $size     Chunk size
	 * @param  Closure $callback Callback function
	 * @param  string  $id       Index field
	 * @return $this
	 */
	public function chunk(int $size, Closure $callback, $id = 'id') {
		$chunk = 1;
		$last_id = 0;
		do {
			$clone = clone $this;
			$clone->where(Argument::column($id), $last_id, '>')->limit($size);
			$compiled = $clone->builder->build($clone->blueprint);
			$rows = $clone->database->query($compiled->getCode(), $compiled->getParameters(), function($stmt) use ($clone) {
				if ($clone->model) {
					$stmt->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, $clone->model, [$clone]);
				}
				return $stmt->fetchAll();
			});
			if ($rows) {
				$count = count($rows);
				$continue = call_user_func($callback, $rows, $count, $chunk);
				if ($continue === false) {
					break;
				}
				$last_id = $rows[$count - 1]->$id;
			} else {
				break;
			}
			$chunk++;
		} while ($size);
		return $this;
	}

	/**
	 * Dump current query
	 * @param  bool  $html Output with HTML tags
	 * @return $this
	 */
	public function dump(bool $html = false) {
		$compiled = $this->builder->build($this->blueprint);
		$console = php_sapi_name() == 'cli';
		$params = var_export($compiled->getParameters(), true);
		$output = sprintf('%s%s%s', $compiled->getCode(), $console ? "\n" : '<br>', $params);
		if ( !$console || $html ) {
			$output = sprintf('<pre>%s</pre>', $output);
		}
		echo $output;
		return $this;
	}

	/**
	 * Build query
	 * @return CompiledQuery
	 */
	public function build(): CompiledQuery {
		return $this->builder->build($this->blueprint);
	}

	/**
	 * Convert to string
	 * @return string
	 */
	public function __toString() {
		$compiled = $this->builder->build($this->blueprint);
		return $compiled->getCode();
	}
}
