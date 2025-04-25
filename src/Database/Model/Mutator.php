<?php

namespace Caldera\Database\Model;

/**
 * Caldera ORM
 * Query builder and ORM layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

use Closure;
use RuntimeException;

class Mutator {

    /**
     * Get value callback
     * @var Closure
     */
    protected Closure $get;

    /*
     * Set value callback
     * @var Closure|null
     */
    protected ?Closure $set;

    /**
     * Constructor
     * @param Closure      $get Get value callback
     * @param Closure|null $set Set value callback
     */
    public function __construct(Closure $get, ?Closure $set = null) {
        $this->get = $get;
        $this->set = $set;
    }

    /**
     * Get value
     * @param  mixed|null $default Default value
     * @return mixed
     */
    public function get(mixed $default = null): mixed {
        return ($this->get)() ?? $default;
    }

    /**
     * Set value
     * @param  mixed $value Value to set
     * @return mixed
     */
    public function set(mixed $value): mixed {
        if ($this->set) {
            return ($this->set)($value);
        } else {
            if (! is_scalar($value) ) {
                throw new RuntimeException('Value must be a scalar or else you must define a set method in the Mutator.');
            }
            return $value;
        }
    }
}