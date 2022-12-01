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
use Caldera\Database\Query\CompiledQuery;
use Caldera\Database\Query\Blueprint;
use Caldera\Database\Query\Argument;
use Caldera\Database\Query\Query;
use Caldera\Database\Query\QueryFactory;

class MySQLBuilder extends AbstractBuilder {

	/**
	 * Build query
	 * @param  Blueprint $blueprint Query blueprint
	 * @return CompiledQuery
	 */
	public function build(Blueprint $blueprint): CompiledQuery {
		$code = '';
		$compiled = new CompiledQuery();
		# Compile the query
		switch ( $blueprint->getType() ) {
			case 'select':
				$code .= $this->compileColumns($blueprint, $compiled, 'SELECT', '');
				$code .= $this->compileTables($blueprint, $compiled, 'FROM', '');
				$code .= $this->compileJoin($blueprint, $compiled, null, '');
				$code .= $this->compileWhere($blueprint, $compiled, 'WHERE', '');
				$code .= $this->compileGroup($blueprint, $compiled, 'GROUP BY', '');
				$code .= $this->compileHaving($blueprint, $compiled, 'HAVING', '');
				$code .= $this->compileOrder($blueprint, $compiled, 'ORDER BY', '');
				$code .= $this->compileLimit($blueprint, $compiled, 'LIMIT', '');
				$code .= $this->compileUnion($blueprint, $compiled, 'UNION', '');
			break;
			case 'insert':
			case 'upsert':
				$code .= $this->compileTables($blueprint, $compiled, 'INSERT INTO', '');
				$code .= $this->compileInsert($blueprint, $compiled, null, '', $blueprint->getType() == 'upsert');
			break;
			case 'update':
				$code .= $this->compileTables($blueprint, $compiled, 'UPDATE', '');
				$code .= $this->compileUpdate($blueprint, $compiled, 'SET', '');
				$code .= $this->compileWhere($blueprint, $compiled, 'WHERE', '');
				$code .= $this->compileOrder($blueprint, $compiled, 'ORDER BY', '');
				$code .= $this->compileLimit($blueprint, $compiled, 'LIMIT', '');
			break;
			case 'delete':
				$code .= $this->compileTables($blueprint, $compiled, 'DELETE FROM', '');
				$code .= $this->compileWhere($blueprint, $compiled, 'WHERE', '');
				$code .= $this->compileOrder($blueprint, $compiled, 'ORDER BY', '');
				$code .= $this->compileLimit($blueprint, $compiled, 'LIMIT', '');
			break;
			case 'truncate':
				$code .= $this->compileTables($blueprint, $compiled, 'TRUNCATE', '');
			break;
		}
		# Set compiled code
		$compiled->setCode($code);
		# And return it
		return $compiled;
	}

	/**
	 * Compile columns
	 * @param  Blueprint $blueprint Query Blueprint
	 * @param  CompiledQuery  $compiled  Compiled Query
	 * @param  mixed     $prefix    Prefix
	 * @param  mixed     $suffix    Suffix
	 * @return string
	 */
	protected function compileColumns(Blueprint $blueprint, CompiledQuery $compiled, $prefix = null, $suffix = null): string {
		$ret = '';
		$fragments = $blueprint->getFragments('columns');
		if ($fragments) {
			$ret .= $prefix !== null ? sprintf('%s ', $prefix) : '';
			#
			$columns = [];
			foreach ($fragments as $fragment) {
				$columns[] = sprintf($fragment->alias ? '%s AS %s' : '%s', $this->backtick($fragment->column), $this->backtick($fragment->alias));
			}
			$ret .= implode(', ', $columns);
			#
			$ret .= $suffix !== null ? sprintf(' %s', $suffix) : '';
		}
		return $ret;
	}

	/**
	 * Compile tables
	 * @param  Blueprint $blueprint Query Blueprint
	 * @param  CompiledQuery  $compiled  Compiled Query
	 * @param  mixed     $prefix    Prefix
	 * @param  mixed     $suffix    Suffix
	 * @return string
	 */
	protected function compileTables(Blueprint $blueprint, CompiledQuery $compiled, $prefix = null, $suffix = null): string {
		$ret = '';
		$fragments = $blueprint->getFragments('tables');
		if ($fragments) {
			$ret .= $prefix !== null ? sprintf('%s ', $prefix) : '';
			#
			$tables = [];
			foreach ($fragments as $fragment) {
				$tables[] = sprintf($fragment->alias ? '%s AS %s' : '%s', $this->backtick($fragment->table), $this->backtick($fragment->alias));
			}
			$ret .= implode(', ', $tables);
			#
			$ret .= $suffix !== null ? sprintf(' %s', $suffix) : '';
		}
		return $ret;
	}

	/**
	 * Compile JOIN statements
	 * @param  Blueprint $blueprint Query Blueprint
	 * @param  CompiledQuery  $compiled  Compiled Query
	 * @param  mixed     $prefix    Prefix
	 * @param  mixed     $suffix    Suffix
	 * @return string
	 */
	protected function compileJoin(Blueprint $blueprint, CompiledQuery $compiled, $prefix = null, $suffix = null): string {
		$ret = '';
		$fragments = $blueprint->getFragments('join');
		if ($fragments) {
			$ret .= $prefix !== null ? sprintf('%s ', $prefix) : '';
			#
			$join = [];
			foreach ($fragments as $fragment) {
				if ( $fragment->first instanceof Closure ) {
					# Get a new Query instance
					$query = QueryFactory::build();
					# Run the callback
					$result = call_user_func($fragment->first, $query);
					# Get the new Query Blueprint
					$blueprint = $query->getBlueprint();
					# Compile WHERE fragments from the Blueprint
					$grouped = $this->compileWhere($blueprint, $compiled);
					# Append the expression
					$binary = $fragment->second ?: 'AND';
					$join[] = sprintf('%s JOIN %s ON %s', strtoupper($fragment->type), $this->backtick($fragment->table), $grouped);
				} else {
					$join[] = sprintf('%s JOIN %s ON %s %s %s', strtoupper($fragment->type), $this->backtick($fragment->table), $this->backtick($fragment->first), $fragment->operator, $this->backtick($fragment->second));
				}
			}
			$ret .= implode(' ', $join);
			#
			$ret .= $suffix !== null ? sprintf(' %s', $suffix) : '';
		}
		return $ret;
	}

	/**
	 * Compile WHERE statements
	 * @param  Blueprint $blueprint Query Blueprint
	 * @param  CompiledQuery  $compiled  Compiled Query
	 * @param  mixed     $prefix    Prefix
	 * @param  mixed     $suffix    Suffix
	 * @return string
	 */
	protected function compileWhere(Blueprint $blueprint, CompiledQuery $compiled, $prefix = null, $suffix = null): string {
		$ret = '';
		$fragments = $blueprint->getFragments('where');
		if ($fragments) {
			$ret .= $prefix !== null ? sprintf('%s ', $prefix) : '';
			#
			$where = [];
			foreach ($fragments as $fragment) {
				if ( $fragment->column instanceof Closure ) {
					# Get a new Query instance
					$query = QueryFactory::build();
					# Run the callback
					$result = call_user_func($fragment->column, $query);
					# Get the new Query Blueprint
					$blueprint = $query->getBlueprint();
					# Compile WHERE fragments from the Blueprint
					$grouped = $this->compileWhere($blueprint, $compiled);
					# Append the expression
					$binary = $fragment->value ?: 'AND';
					$where[] = sprintf('%s (%s)', $binary, $grouped);
				} else {
					if ( $fragment->value instanceof Argument ) {
						$value = $fragment->value->compile();
					} else {
						if ( is_array($fragment->value) ) {
							$value = sprintf('(%s)', str_repeat('?, ', count($fragment->value) - 1) . '?');
						} else {
							$value = '?';
						}
						$compiled->addParameter($fragment->value);
					}
					#
					if ($value) {
						$where[] = sprintf('%s %s %s %s', $fragment->boolean, $this->backtick($fragment->column), $fragment->operator, $value);
					}
				}
			}
			$conditions = implode(' ', $where);
			$ret .= preg_replace('/^(AND|OR)\s?/', '', $conditions);
			#
			$ret .= $suffix !== null ? sprintf(' %s', $suffix) : '';
		}
		return $ret;
	}

	/**
	 * Compile HAVING statements
	 * @param  Blueprint $blueprint Query Blueprint
	 * @param  CompiledQuery  $compiled  Compiled Query
	 * @param  mixed     $prefix    Prefix
	 * @param  mixed     $suffix    Suffix
	 * @return string
	 */
	protected function compileHaving(Blueprint $blueprint, CompiledQuery $compiled, $prefix = null, $suffix = null): string {
		$ret = '';
		$fragments = $blueprint->getFragments('having');
		if ($fragments) {
			$ret .= $prefix !== null ? sprintf('%s ', $prefix) : '';
			#
			$having = [];
			foreach ($fragments as $fragment) {
				if ( $fragment->column instanceof Closure ) {
					# Get a new Query instance
					$query = QueryFactory::build();
					# Run the callback
					$result = call_user_func($fragment->column, $query);
					# Get the new Query Blueprint
					$blueprint = $query->getBlueprint();
					# Compile WHERE fragments from the Blueprint
					$grouped = $this->compileHaving($blueprint, $compiled);
					# Append the expression
					$binary = $fragment->value ?: 'AND';
					$having[] = sprintf('%s (%s)', $binary, $grouped);
				} else {
					if ( $fragment->value instanceof Argument ) {
						$value = $fragment->value->compile();
					} else {
						$compiled->addParameter($fragment->value);
						$value = '?';
					}
					#
					if ($value) {
						$having[] = sprintf('%s %s %s %s', $fragment->boolean, $this->backtick($fragment->column), $fragment->operator, $value);
					}
				}
			}
			$conditions = implode(' ', $having);
			$ret .= preg_replace('/^(AND|OR)\s?/', '', $conditions);
			#
			$ret .= $suffix !== null ? sprintf(' %s', $suffix) : '';
		}
		return $ret;
	}

	/**
	 * Compile ORDER statements
	 * @param  Blueprint $blueprint Query Blueprint
	 * @param  CompiledQuery  $compiled  Compiled Query
	 * @param  mixed     $prefix    Prefix
	 * @param  mixed     $suffix    Suffix
	 * @return string
	 */
	protected function compileOrder(Blueprint $blueprint, CompiledQuery $compiled, $prefix = null, $suffix = null): string {
		$ret = '';
		$fragments = $blueprint->getFragments('order');
		if ($fragments) {
			$ret .= $prefix !== null ? sprintf('%s ', $prefix) : '';
			#
			$order = [];
			foreach ($fragments as $fragment) {
				$order[] = sprintf('%s %s', $this->backtick($fragment->column), strtoupper($fragment->sort));
			}
			$ret .= implode(', ', $order);
			#
			$ret .= $suffix !== null ? sprintf(' %s', $suffix) : '';
		}
		return $ret;
	}

	/**
	 * Compile GROUP statements
	 * @param  Blueprint $blueprint Query Blueprint
	 * @param  CompiledQuery  $compiled  Compiled Query
	 * @param  mixed     $prefix    Prefix
	 * @param  mixed     $suffix    Suffix
	 * @return string
	 */
	protected function compileGroup(Blueprint $blueprint, CompiledQuery $compiled, $prefix = null, $suffix = null): string {
		$ret = '';
		$fragments = $blueprint->getFragments('group');
		if ($fragments) {
			$ret .= $prefix !== null ? sprintf('%s ', $prefix) : '';
			#
			$group = [];
			foreach ($fragments as $fragment) {
				$group[] = $this->backtick($fragment->column);
			}
			$ret .= implode(', ', $group);
			#
			$ret .= $suffix !== null ? sprintf(' %s', $suffix) : '';
		}
		return $ret;
	}

	/**
	 * Compile LIMIT statements
	 * @param  Blueprint $blueprint Query Blueprint
	 * @param  CompiledQuery  $compiled  Compiled Query
	 * @param  mixed     $prefix    Prefix
	 * @param  mixed     $suffix    Suffix
	 * @return string
	 */
	protected function compileLimit(Blueprint $blueprint, CompiledQuery $compiled, $prefix = null, $suffix = null): string {
		$ret = '';
		$fragments = $blueprint->getFragments('limit');
		if ($fragments) {
			$ret .= $prefix !== null ? sprintf('%s ', $prefix) : '';
			#
			$limit = [];
			foreach ($fragments as $fragment) {
				$limit[] = sprintf($fragment->offset ? '%d, %d' : '%d', $fragment->limit, $fragment->offset);
			}
			$ret .= implode(', ', $limit);
			#
			$ret .= $suffix !== null ? sprintf(' %s', $suffix) : '';
		}
		return $ret;
	}

	/**
	 * Compile UNION statements
	 * @param  Blueprint $blueprint Query Blueprint
	 * @param  CompiledQuery  $compiled  Compiled Query
	 * @param  mixed     $prefix    Prefix
	 * @param  mixed     $suffix    Suffix
	 * @return string
	 */
	protected function compileUnion(Blueprint $blueprint, CompiledQuery $compiled, $prefix = null, $suffix = null): string {
		$ret = '';
		$fragments = $blueprint->getFragments('union');
		if ($fragments) {
			$ret .= $prefix !== null ? sprintf('%s ', $prefix) : '';
			#
			$union = [];
			foreach ($fragments as $fragment) {
				$subquery = $fragment->query->build();
				$union[] = $subquery->getCode();
				$compiled->addParameter( $subquery->getParameters() );
			}
			$ret .= implode(', ', $union);
			#
			$ret .= $suffix !== null ? sprintf(' %s', $suffix) : '';
		}
		return $ret;
	}

	/**
	 * Compile INSERT statements
	 * @param  Blueprint      $blueprint Query Blueprint
	 * @param  CompiledQuery  $compiled  Compiled Query
	 * @param  mixed          $prefix    Prefix
	 * @param  mixed          $suffix    Suffix
	 * @param  bool           $upsert    Whether is upsert or not
	 * @return string
	 */
	protected function compileInsert(Blueprint $blueprint, CompiledQuery $compiled, $prefix = null, $suffix = null, $upsert = false): string {
		$ret = '';
		$fragment = $blueprint->getFragments('insert', true);
		if ($fragment) {
			$ret .= $prefix !== null ? sprintf('%s ', $prefix) : '';
			#
			$insert = $fragment->insert;
			$update = $upsert ? $fragment->update : null;
			$columns = [];
			$rows = [];
			$single = true;
			$first = true;
			foreach ($insert as $name => $value) {
				if ( is_array($value) ) {
					$single = false;
					$items = [];
					$row = [];
					foreach ($value as $name => $subvalue) {
						if ($first) {
							$columns[] = $this->backtick($name);
						}
						if ( $subvalue instanceof Argument ) {
							$row[] = $subvalue->compile();
						} else {
							$row[] = '?';
							$compiled->addParameter($subvalue);
						}
					}
					$first = false;
					$items[] = implode(', ', $row);
					$rows[] = sprintf('(%s)', implode(', ', $items) );
				} else {
					$columns[] = $this->backtick($name);
					$row = [];
					if ( $value instanceof Argument ) {
						$row[] = $value->compile();
					} else {
						$row[] = '?';
						$compiled->addParameter($value);
					}
					$rows[] = implode(', ', $row);
				}
			}
			$ret .= sprintf('(%s) VALUES ', implode(', ', $columns) );
			$ret .= sprintf($single ? '(%s)' : '%s', implode(', ', $rows) );
			#
			if ($update && $upsert) {
				$updates = [];
				$add_alias = false;
				foreach ($update as $column => $value) {
					if ( $value instanceof Argument ) {
						$updates[] = sprintf('%s = %s', $this->backtick($column), $value->compile());
						$add_alias = true;
					} else {
						$updates[] = sprintf('%s = %s', $this->backtick($column), '?');
						$compiled->addParameter($value);
					}
				}
				$ret .= $add_alias ? ' AS `row`' : '';
				$ret .= $updates ? sprintf(' ON DUPLICATE KEY UPDATE %s', implode(', ', $updates) ) : '';
			}
			#
			$ret .= $suffix !== null ? sprintf(' %s', $suffix) : '';
		}
		return $ret;
	}

	/**
	 * Compile UPDATE statements
	 * @param  Blueprint $blueprint Query Blueprint
	 * @param  CompiledQuery  $compiled  Compiled Query
	 * @param  mixed     $prefix    Prefix
	 * @param  mixed     $suffix    Suffix
	 * @return string
	 */
	protected function compileUpdate(Blueprint $blueprint, CompiledQuery $compiled, $prefix = null, $suffix = null): string {
		$ret = '';
		$fragment = $blueprint->getFragments('update', true);
		if ($fragment) {
			$ret .= $prefix !== null ? sprintf('%s ', $prefix) : '';
			#
			$update = $fragment->update;
			$columns = [];
			$rows = [];
			$single = true;
			foreach ($update as $name => $value) {
				if ( $value instanceof Argument ) {
					$value_str = $value->compile();
				} else {
					$value_str = '?';
					$compiled->addParameter($value);
				}
				$columns[] = sprintf('%s = %s', $this->backtick($name), $value_str);
			}
			$ret .= implode(', ', $columns);
			#
			$ret .= $suffix !== null ? sprintf(' %s', $suffix) : '';
		}
		return $ret;
	}
}
