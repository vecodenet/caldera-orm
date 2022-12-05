<?php

declare(strict_types = 1);

/**
 * Caldera ORM
 * Query builder and ORM layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Query;

class Fragment {

	/**
	 * Fragment data
	 * @var array
	 */
	protected $data = [];

	/**
	 * Constructor
	 * @param array $data Fragment data
	 */
	public function __construct(array $data = []) {
		$this->data = $data;
	}

	/**
	 * Get fragment data
	 * @return array
	 */
	public function getData(): array {
		return $this->data;
	}

	/**
	 * Get data item
	 * @return mixed
	 */
	public function __get(string $name) {
		return $this->data[$name] ?? null;
	}
}
