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
use Caldera\Database\Query\QueryFactory;

class SQLiteBuilder extends MySQLBuilder {

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
				$code .= $this->compileTables($blueprint, $compiled, 'DELETE FROM', '');
			break;
		}
		# Set compiled code
		$compiled->setCode($code);
		# And return it
		return $compiled;
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
			$index = $upsert ? $fragment->index : null;
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
				foreach ($update as $column => $value) {
					if ( $value instanceof Argument ) {
						$updates[] = sprintf('%s = %s', $this->backtick($column), $value->compile());
					} else {
						$updates[] = sprintf('%s = %s', $this->backtick($column), '?');
						$compiled->addParameter($value);
					}
				}
				$indexes = [];
				foreach ($index as $column) {
					if ( $column instanceof Argument ) {
						$indexes[] = $column->compile();
					} else {
						$indexes[] = $this->backtick($column);
					}
				}
				$ret .= $updates ? sprintf(' ON CONFLICT (%s) DO UPDATE SET %s', implode(', ', $indexes), implode(', ', $updates) ) : '';
			}
			#
			$ret .= $suffix !== null ? sprintf(' %s', $suffix) : '';
		}
		return $ret;
	}
}
