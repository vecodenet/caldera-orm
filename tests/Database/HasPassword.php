<?php

declare(strict_types = 1);

/**
 * Caldera ORM
 * Query builder and ORM layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Database;

use Caldera\Database\Model\Mutator;

trait HasPassword {

    protected function passwordEncoder(): Mutator {
        return new Mutator(function() {
            return $this->properties['password'];
        }, function(mixed $value) {
            # WARNING: THIS IS VERY UNSAFE!! - But we need a consistent result so we can not use password_hash as the result varies each time
            # But again, DON'T USE MD5 FOR PASSWORDS!!!
            return md5($value);
        });
    }
}