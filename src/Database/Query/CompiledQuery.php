<?php

declare(strict_types = 1);

/**
 * Caldera ORM
 * ORM implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Query;

class CompiledQuery {

	/**
	 * Query parameters
	 * @var array
	 */
	protected $parameters = [];

	/**
	 * Query code
	 * @var string
	 */
	protected $code = '';

	/**
	 * Get code
	 * @return string
	 */
	public function getCode(): string {
		return trim($this->code);
	}

	/**
	 * Get parameters
	 * @return array
	 */
	public function getParameters(): array {
		return $this->parameters;
	}

	/**
	 * Set code
	 * @param string $code Query code
	 * @return $this
	 */
	public function setCode(string $code) {
		$this->code = $code;
		return $this;
	}

	/**
	 * Add a parameter
	 * @param mixed $parameter Parameter or array of parameters
	 * @return $this
	 */
	public function addParameter($parameter) {
		if ( is_array($parameter) ) {
			$this->parameters = array_merge($this->parameters, $parameter);
		} else {
			array_push($this->parameters, $parameter);
		}
		return $this;
	}
}
