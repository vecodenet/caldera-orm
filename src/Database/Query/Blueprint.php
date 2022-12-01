<?php

declare(strict_types = 1);

/**
 * Caldera ORM
 * ORM implementation, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Query;

class Blueprint {

	/**
	 * Query fragments
	 * @var array
	 */
	protected $fragments = [];

	/**
	 * Query type
	 * @var string
	 */
	protected $type = 'select';

	/**
	 * Add fragment
	 * @param  string   $type     Fragment type
	 * @param  Fragment $fragment Fragment object
	 * @return $this
	 */
	public function addFragment(string $type, Fragment $fragment) {
		if (! isset( $this->fragments[$type] ) ) {
			$this->resetFragment($type);
		}
		$this->fragments[$type][] = (object) $fragment;
		return $this;
	}

	/**
	 * Pop fragment
	 * @param  string $type Fragment type
	 * @return Fragment
	 */
	public function popFragment(string $type): Fragment {
		$ret = null;
		if ( isset( $this->fragments[$type] ) ) {
			$ret = array_pop($this->fragments[$type]);
		}
		return $ret;
	}

	/**
	 * Shift fragment
	 * @param  string $type Fragment type
	 * @return Fragment
	 */
	public function shiftFragment(string $type): Fragment {
		$ret = null;
		if ( isset( $this->fragments[$type] ) ) {
			$ret = array_shift($this->fragments[$type]);
		}
		return $ret;
	}

	/**
	 * Reset fragment
	 * @param  string $type Fragment type
	 * @return $this
	 */
	public function resetFragment(string $type) {
		$this->fragments[$type] = [];
		return $this;
	}

	/**
	 * Get type
	 * @return mixed
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Set type
	 * @param string $type Query type
	 * @return $this
	 */
	public function setType(string $type) {
		$this->type = $type;
		return $this;
	}

	/**
	 * Get fragments
	 * @param  string $type   Fragment type
	 * @param  bool   $single Return single Fragment
	 * @return mixed
	 */
	public function getFragments(string $type, bool $single = false) {
		$ret = isset($this->fragments[$type]) ? $this->fragments[$type] : null;
		if ($ret && $single) {
			$ret = array_shift($ret);
		}
		return $ret;
	}
}
