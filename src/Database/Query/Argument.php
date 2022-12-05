<?php

declare(strict_types = 1);

/**
 * Caldera ORM
 * Query builder and ORM layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Query;

class Argument {

	/**
	 * Argument type
	 * @var string
	 */
	protected $type;

	/**
	 * Argument value
	 * @var mixed
	 */
	protected $value;

	/**
	 * Argument parameters
	 * @var array
	 */
	protected $parameters = [];

	/**
	 * Constructor
	 * @param string $type       Argument type
	 * @param mixed  $value      Argument value
	 * @param mixed  $parameters Argument parameters
	 */
	public function __construct(string $type, $value, ...$parameters) {
		$this->type = $type;
		$this->value = $value;
		$this->parameters = $parameters;
	}

	/**
	 * Create a raw, unescaped argument
	 * @param  mixed    $value Argument value
	 * @return Argument
	 */
	static function raw($value): Argument {
		return new Argument('raw', $value);
	}

	/**
	 * Create an argument that refers to a method
	 * @param  mixed    $value      Argument name
	 * @param  mixed    $parameters Argument parameters
	 * @return Argument
	 */
	static function method($value, ...$parameters): Argument {
		return new Argument('method', $value, ...$parameters);
	}

	/**
	 * Create an argument that refers to a column
	 * @param  mixed    $value Column name
	 * @param  mixed    $alias Column alias
	 * @return Argument
	 */
	static function column($value, ...$alias): Argument {
		return new Argument('column', $value, ...$alias);
	}

	/**
	 * Create an argument that refers to a table
	 * @param  mixed    $value Table name
	 * @param  mixed    $alias Table alias
	 * @return Argument
	 */
	static function table($value, ...$alias): Argument {
		return new Argument('table', $value, ...$alias);
	}

	/**
	 * Compile argument
	 * @return string
	 */
	public function compile(): string {
		$ret = null;
		$parameters = null;
		switch ($this->type) {
			case 'raw':
				$ret = $this->value;
			break;
			case 'method':
				$parameters = '';
				if ($this->parameters) {
					$temp = [];
					foreach ($this->parameters as $parameter) {
						if ( $parameter instanceof Argument ) {
							$temp[] = $parameter->compile();
						} else {
							$temp[] = $parameter == '*' ? $parameter : sprintf('`%s`', $parameter);
						}
					}
					$parameters = implode(', ', $temp);
				}
				$ret = sprintf('%s(%s)', $this->value, $parameters);
			break;
			case 'table':
				$parts = explode('.', $this->value);
				$parts = array_map(function($part) {
					return sprintf('`%s`', $part);
				}, $parts);
				$ret = implode('.', $parts);
				if ($this->parameters) {
					$alias = $this->parameters[0];
					$ret .= sprintf(' AS `%s`', $alias);
				}
			break;
			case 'column':
				if ($this->value == '*') {
					$ret = $this->value;
				} else {
					$parts = explode('.', $this->value);
					$parts = array_map(function($part) {
						return sprintf('`%s`', $part);
					}, $parts);
					$ret = implode('.', $parts);
					if ($this->parameters) {
						$alias = $this->parameters[0];
						$ret .= sprintf(' AS `%s`', $alias);
					}
				}
			break;
		}
		return $ret;
	}
}
