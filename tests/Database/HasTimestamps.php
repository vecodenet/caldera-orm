<?php

declare(strict_types = 1);

/**
 * Caldera ORM
 * Query builder and ORM layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Database;

use DateTime;

use Caldera\Database\Model\Mutator;

trait HasTimestamps {

    protected static function created(): Mutator {
        return new Mutator(function($value) {
            return new DateTime($value);
        }, function(mixed $value) {
            return ($value instanceof DateTime) ? $value->format('Y-m-d H:i:s') : (is_string($value) ? $value : '');
        });
    }

    protected static function modified(): Mutator {
        # Intentionally defined without a set callback for testing purposes
        return new Mutator(function($value) {
            return new DateTime($value);
        });
    }
}