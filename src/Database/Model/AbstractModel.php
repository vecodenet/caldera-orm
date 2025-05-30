<?php

declare(strict_types = 1);

/**
 * Caldera ORM
 * Query builder and ORM layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Database\Model;

use Closure;
use JsonSerializable;
use ReflectionClass;
use ReflectionNamedType;

use Caldera\Database\Query\Query;
use Caldera\Database\Query\QueryFactory;

abstract class AbstractModel implements JsonSerializable {

	/**
	 * Table name
	 * @var string
	 */
	protected static $table  = '';

	/**
	 * Model name
	 * @var string
	 */
	protected static $model  = '';

	/**
	 * Model fields
	 * @var array
	 */
	protected static $fields = [];

	/**
	 * Model fields for update
	 * @var array
	 */
	protected static $update = [];

	/**
	 * Default values
	 * @var array
	 */
	protected static $defaults = [];

	/**
	 * Primary field name
	 * @var string
	 */
	protected static $field_primary = 'id';

	/**
	 * Created field name
	 * @var string
	 */
	protected static $field_created = 'created';

	/**
	 * Updated field name
	 * @var string
	 */
	protected static $field_updated = 'modified';

    /**
     * @var array<string, array>
     */
    protected static $mutators = [];

    /**
     * @var array<string, string>
     */
    protected static $mutator_aliases = [];

    /**
     * @var array<string, array>
     */
    protected static $reflection_cache = [];

	/**
	 * Model properties
	 * @var array
	 */
	protected $properties = [];

	/**
	 * Constructor
	 * @param Query $query Query object
	 */
	function __construct(Query $query = null) {
		if (! $query ) {
			if (static::$fields) {
				foreach (static::$fields as $field) {
					switch ($field) {
						case static::$field_primary:
							$value = 0;
						break;
						case static::$field_created:
						case static::$field_updated:
							$value = '0000-00-00 00:00:00';
						break;
						default:
							$value = isset( static::$defaults[$field] ) ? static::$defaults[$field] : '';
						break;
					}
					$this->properties[$field] = $value;
				}
			}
		}
	}

	/**
	 * Query the model table
	 * @return Query
	 */
	public static function query(): Query {
		$query = QueryFactory::build(static::$table);
		$query->setModel( get_called_class() );
		return $query;
	}

	/**
	 * Get all rows from the model table
	 * @param  mixed $columns Which columns to get
	 * @return mixed
	 */
	public static function all($columns = '*') {
		$query = self::query();
		if ( is_array($columns) ) {
			foreach ($columns as $column) {
				$query->column($column);
			}
		} else {
			$query->column($columns);
		}
		$ret = $query->all();
		return $ret;
	}

	/**
	 * Get a single row from the model table
	 * @param  mixed $columns Which columns to get
	 * @return mixed
	 */
	public static function first($columns = '*') {
		$query = self::query();
		if ( is_array($columns) ) {
			foreach ($columns as $column) {
				$query->column($column);
			}
		} else {
			$query->column($columns);
		}
		$ret = $query->first();
		return $ret;
	}

	/**
	 * Get a row by its primary ID
	 * @param  mixed $id      Entity ID
	 * @param  mixed $columns Which columns to get
	 * @return mixed
	 */
	public static function get($id, $columns = '*') {
		$ret = self::getBy(static::$field_primary, $id, $columns);
		return $ret;
	}

	/**
	 * Get a row by an specific field
	 * @param  string $field   Field name
	 * @param  mixed  $value   Field value
	 * @param  mixed  $columns Which columns to get
	 * @return mixed
	 */
	public static function getBy($field, $value, $columns = '*') {
		$query = self::query();
		if ( is_array($columns) ) {
			foreach ($columns as $column) {
				$query->column($column);
			}
		} else {
			$query->column($columns);
		}
		$ret = $query->where($field, $value)->first();
		return $ret;
	}

	/**
	 * Get the number of rows
	 * @param  mixed $column Which column to use
	 * @return int
	 */
	public static function count($column = null): int {
		$column = $column === null ? static::$field_primary : $column;
		return self::query()->count($column);
	}

	/**
	 * Get minimum value
	 * @param  mixed $column Which column to use
	 * @return mixed
	 */
	public static function min($column) {
		return self::query()->min($column);
	}

	/**
	 * Get maximum value
	 * @param  mixed $column Which column to use
	 * @return mixed
	 */
	 static function max($column) {
		return self::query()->max($column);
	}

	/**
	 * Get average value
	 * @param  mixed $column Which column to use
	 * @return mixed
	 */
	public static function avg($column) {
		return self::query()->avg($column);
	}

	/**
	 * Get sum value
	 * @param  mixed $column Which column to use
	 * @return mixed
	 */
	public static function sum($column) {
		return self::query()->sum($column);
	}

	/**
	 * Add where clause
	 * @param  mixed  $column   Column
	 * @param  mixed  $value    Column value
	 * @param  string $operator Comparison operator
	 * @param  string $boolean  Boolean operator
	 * @return Query
	 */
	public static function where($column, $value = null, string $operator = '=', string $boolean = 'AND'): Query {
		$query = self::query();
		return $query->where($column, $value, $operator, $boolean);
	}

	/**
	 * Add where clause with an IN operator
	 * @param  mixed  $column   Column
	 * @param  array  $values   Column values
	 * @param  string $boolean  Boolean operator
	 * @return Query
	 */
	public static function whereIn($column, array $values, string $boolean = 'AND'): Query {
		$query = self::query();
		return $query->whereIn($column, $values, $boolean);
	}

	/**
	 * Add where clause with a NOT IN operator
	 * @param  mixed  $column   Column
	 * @param  array  $values   Column values
	 * @param  string $boolean  Boolean operator
	 * @return Query
	 */
	public static function whereNotIn($column, array $values, string $boolean = 'AND'): Query {
		$query = self::query();
		return $query->whereNotIn($column, $values, $boolean);
	}

	/**
	 * Add where clause for two columns
	 * @param  mixed  $column   Column
	 * @param  mixed  $value    Column value
	 * @param  string $operator Comparison operator
	 * @param  string $boolean  Boolean operator
	 * @return Query
	 */
	public static function whereColumn($column, $value, string $operator = '=', string $boolean = 'AND'): Query {
		$query = self::query();
		return $query->whereColumn($column, $value, $operator, $boolean);
	}

	/**
	 * Get chunked rows
	 * @param  int     $size     Chunk size
	 * @param  Closure $callback Callback function
	 * @return mixed
	 */
	public static function chunk(int $size, Closure $callback) {
		self::query()->chunk($size, $callback, static::$field_primary);
	}

	/**
	 * Delete entity
	 * @return void
	 */
	public function delete(): void {
		$query = static::query();
		$this->deleteProc($query);
	}

	/**
	 * Save entity
	 * @return void
	 */
	public function save(): void {
		$query = static::query();
		$ref = static::$field_primary;
		if ($this->properties[$ref] == 0) {
			$this->saveProc($query);
		} else {
			$this->updateProc($query);
		}
	}

	/**
	 * Reload fields from database
	 * @return void
	 */
	public function refresh(): void {
		$query = static::query();
		$ref = static::$field_primary;
		if ($this->properties[$ref] > 0) {
			$this->refreshProc($query);
		}
	}

	/**
	 * Get table name
	 * @return string
	 */
	public function getTable() {
		return static::$table;
	}

	/**
	 * Get field names
	 * @param  bool  $update Return fields for update only
	 * @return array
	 */
	public function getFields($update = false) {
		return $update ? static::$update : static::$fields;
	}

	/**
	 * Save procedure
	 * @param  Query $query Current Query object
	 * @return void
	 */
	protected function saveProc(Query $query): void {
		# Touch timestamp
		$ref = static::$field_created;
		$this->properties[$ref] = date('Y-m-d H:i:s');
		# Callback before
		$continue = $this->beforeSave();
		if ($continue !== false) {
			# Add fields
			$fields = [];
			foreach (static::$fields as $field) {
				$fields[$field] = $this->properties[$field];
			}
			# Insert row
			$id = $query->insert($fields);
			# Update primary field
			$ref = static::$field_primary;
			$this->properties[$ref] = $id;
			# Callback after
			if ($this->properties[$ref] > 0) {
				$this->afterSave();
			}
		}
	}

	/**
	 * Update procedure
	 * @param  Query $query Current Query object
	 * @return void
	 */
	protected function updateProc(Query $query): void {
		# Touch timestamp
		$ref = static::$field_updated;
		$this->properties[$ref] = date('Y-m-d H:i:s');
		# Callback before
		$continue = $this->beforeSave();
		if ($continue !== false) {
			# Add fields
			$fields = [];
			foreach (static::$update as $field) {
				$fields[$field] = $this->properties[$field];
			}
			# Update row
			$ref = static::$field_primary;
			$query->where(static::$field_primary, $this->properties[$ref])->update($fields);
			# Callback after
			$this->afterSave();
		}
	}

	/**
	 * Delete procedure
	 * @param  Query $query Current Query object
	 * @return void
	 */
	protected function deleteProc(Query $query): void {
		# Callback before
		$continue = $this->beforeDelete();
		if ($continue !== false) {
			$ref = static::$field_primary;
			$query->where(static::$field_primary, $this->properties[$ref])->limit(1)->delete();
			# Callback after
			$this->afterDelete();
		}
	}

	/**
	 * Refresh procedure
	 * @param  Query $query Current Query object
	 * @return void
	 */
	protected function refreshProc(Query $query): void {
		# Callback before
		$continue = $this->beforeRefresh();
		if ($continue !== false) {
			# Fetch by primary key
			$ref = static::$field_primary;
			$item = $query->where(static::$field_primary, $this->properties[$ref])->first();
			if ($item) {
				foreach (static::$fields as $field) {
					$this->properties[$field] = $item->$field;
				}
			}
			# Callback after
			$this->afterRefresh();
		}
	}

	/**
	 * Before save callback
	 * @return bool
	 */
	protected function beforeSave(): bool {
		# Placeholder
		return true;
	}

	/**
	 * After save callback
	 * @return void
	 */
	protected function afterSave(): void {
		# Placeholder
	}

	/**
	 * Before delete callback
	 * @return bool
	 */
	protected function beforeDelete() : bool{
		# Placeholder
		return true;
	}

	/**
	 * After delete callback
	 * @return void
	 */
	protected function afterDelete(): void {
		# Placeholder
	}

	/**
	 * Before refresh callback
	 * @return bool
	 */
	protected function beforeRefresh(): bool {
		# Placeholder
		return true;
	}

	/**
	 * After refresh callback
	 * @return void
	 */
	protected function afterRefresh(): void {
		# Placeholder
	}

	/**
	 * jsonSerialize implementation placeholder
	 * @return mixed
	 */
	public function jsonSerialize(): mixed {
		# Placeholder
		return $this->properties;
	}

	/**
	 * Check if the specified property is set
	 * @param  string  $name Property name
	 * @return bool
	 */
	public function hasProperty(string $name): bool {
		return isset( $this->properties[$name] );
	}

	/**
	 * Get the specified property
	 * @param  string $name    Property name
	 * @param  mixed  $default Default value
	 * @return mixed
	 */
	public function getProperty(string $name, mixed $default = null): mixed {
        $mutator = $this->checkMutator($name);
        if ($mutator) {
            # Pass the property through the mutator and return the result
            return $mutator->get( $this->properties[$name] );
        }
		return $this->properties[$name] ?? $default;
	}

	/**
	 * Set the specified property
	 * @param string $name  Property name
	 * @param mixed  $value Property value
	 */
	public function setProperty(string $name, mixed $value): void {
        $mutator = $this->checkMutator($name);
        if ($mutator) {
            # Pass the property through the mutator and save the result
            $this->properties[$name] = $mutator->set($value);
        } else {
		    $this->properties[$name] = $value;
        }
	}

	/**
	 * Get property
	 * @param  string $name Property name
	 * @return mixed
	 */
	public function __get(string $name): mixed {
		return $this->getProperty($name);
	}

	/**
	 * Set property
	 * @param  string $name  Property name
	 * @param  mixed  $value Property value
	 * @return void
	 */
	public function __set(string $name, mixed $value): void {
		$this->setProperty($name, $value);
	}

    /**
     * Check if a mutator exists for a given property and return it
     * @param  string $property
     * @return Mutator|null
     */
    protected function checkMutator(string $property): ?Mutator {
        $class = static::class;
        if (! isset( static::$mutators[$class] ) ) {
            # Create the array of mutators for the concrete model class
            static::$mutators[$class] = [];
        }
        # Get mutator alias, if any
        $property = static::$mutator_aliases[$property] ?? $property;
        # Try to retrieve the mutator from the cache
        $mutator = static::$mutators[$class][$property] ?? null;
        # Get a ReflectionClass if there is no cached mutator
        $reflected = !$mutator ? self::getReflectedClass($this) : null;
        # If there is no mutator try to get it from the AbstractModel, but avoid properties that have been already checked and  should be ignored
        if (!$mutator && $reflected && !in_array($property, self::$reflection_cache[$class]['skip']) && $reflected->hasMethod($property)) {
            # Check if there is a method that matches the property name
            $method = $reflected->getMethod($property);
            $return_type = $method->getReturnType();
            # Check the return type of the method
            if ($return_type instanceof ReflectionNamedType && $return_type->getName() == Mutator::class) {
                # Build the mutator instance and save it to the cache
                $mutator = call_user_func([$this, $property]); # @phpstan-ignore-line
                static::$mutators[$class][$property] = $mutator;
            } else {
                # Add the property to the ignore list
                self::$reflection_cache[$class]['skip'][] = $property;
            }
        }
        return $mutator;
    }

    /**
     * Get a ReflectionClass from the cache or create a new one and cache it
     * @param AbstractModel $model
     * @return ReflectionClass|null
     */
    protected static function getReflectedClass(AbstractModel $model): ?ReflectionClass {
        $class = get_class($model);
        # Check the reflection cache
        $cached = self::$reflection_cache[$class] ?? null;
        if (!$cached) {
            # No cached instance, so create a new entry one and cache it
            $reflected = new ReflectionClass($model);
            self::$reflection_cache[$class] = [
                'reflected' => $reflected,
                'skip' => [],
            ];
        } else {
            # Take the instance from the cached entry
            $reflected = $cached['reflected'] ?? null;
        }
        return $reflected;
    }
}
