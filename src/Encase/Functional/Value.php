<?php
namespace Encase\Functional;

use Countable;
use ArrayAccess;
use ArrayIterator;
use CachingIterator;
use JsonSerializable;
use IteratorAggregate;
use function func_get_args;
use function array_key_exists;
use Encase\Functional\ValueIterator;
use Encase\Functional\Traits\Functional;

/**
 * An immutable wrapper for any kind of variable, which proxies method calls to
 * Functional functions and allows chaining.
 *
 * Returns from some proxied calls are also wrapped in Value instances.
 *
 * @method int count()
 * @method int size()
 * @method string|bool isType(string|array $type)
 * @method static|$this split(string $separator = '', int $limit = null)
 * @method static|$this type()
 */
class Value implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
	use Functional;

	/** @var mixed */
	protected $value;

	/**
	 * @param  mixed  $value
	 */
	public function __construct($value = null)
	{
		$this->value = $value instanceof self ? $value->get() : $value;
	}

	/**
	 * Get the managed value.
	 *
	 * @return mixed
	 */
	public function get()
	{
		return $this->value;
	}

	/**
	 * Get the size of the value.
	 *
	 * @return int
	 */
	public function count(): int
	{
		return size($this->value);
	}

	/**
	 * Check if the value is the same as the given value (strict equality).
	 *
	 * @param  mixed  $value
	 * @return bool
	 */
	public function is($value): bool
	{
		return $this->value === $value;
	}

	/**
	 * Check if the value is equivalent to the given value (loose equality).
	 *
	 * @param  mixed  $value
	 * @return bool
	 */
	public function equals($value): bool
	{
		return $this->value == $value;
	}

	/**
	 * Check if the value is empty.
	 *
	 * @return bool
	 */
	public function isEmpty(): bool
	{
		return empty($this->value);
	}

	/**
	 * Check if the value is null.
	 *
	 * @return bool
	 */
	public function isNull(): bool
	{
		return $this->value === null;
	}

	/**
	 * Handles chainable method calls to Functional functions.
	 * Returns $this if the method does not mutate or returns a new value.
	 * Otherwise, a new Value instance is returned containing the value.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return static|$this
	 */
	public function __call($method, $params = [])
	{
		// Call the Functional function.
		$result = $this->callFunctionalMethod($this->value, $method, $params);

		// If the function returns an unmutated copy of its input, we'll return
		// this instance to allow chaining.
		if ($this->isMethodTapped($method)) {
			return $this;
		}

		// If the function returns a mutated copy of its input, we'll return it
		// wrapped in a new Value instance to allow chaining.
		if ($this->isMethodAMutator($method) && !($result instanceof self)) {
			return new self($result);
		}

		// For totally new values being returned, return it without wrapping.
		return $result;
	}

	/**
	 * Create a new object instance.
	 *
	 * @param  mixed  $value
	 * @return static|self
	 */
	public static function make($value = null)
	{
		return $value instanceof static ? $value : new static($value);
	}

	/**
	 * Serialise the items as an array.
	 *
	 * @return array
	 */
	public function jsonSerialize()
	{
		return $this->value;
	}

	/**
	 * Get an iterator for the value.
	 *
	 * @return \Iterator
	 */
	public function getIterator()
	{
		return new ValueIterator($this->value);
	}

	/**
	 * Get a CachingIterator instance.
	 *
	 * @param  int  $flags
	 * @return \CachingIterator
	 */
	public function getCachingIterator($flags = CachingIterator::CALL_TOSTRING)
	{
		return new CachingIterator($this->getIterator(), $flags);
	}

	/**
	 * Check if the value has the given offset.
	 *
	 * @param  mixed  $key
	 * @return bool
	 */
	public function offsetExists($key): bool
	{
		return array_key_exists($key, $this->value);
	}

	/**
	 * Access the given offset of the value.
	 *
	 * @param  mixed  $key
	 * @return self
	 */
	public function offsetGet($key)
	{
		return new self($this->value[$key]);
	}

	/**
	 * Set the given offset of the value.
	 *
	 * @param  mixed  $key
	 * @param  mixed  $value
	 */
	public function offsetSet($key, $value): void
	{
		if ($key === null) {
			$this->value[] = $value;
		} else {
			$this->value[$key] = $value;
		}
	}

	/**
	 * Unset the given offset of the value.
	 *
	 * @param  string  $key
	 */
	public function offsetUnset($key): void
	{
		unset($this->value[$key]);
	}

	/**
	 * Call the value as a function.
	 *
	 * @param  mixed  ...$parameters
	 * @return void
	 */
	public function __invoke()
	{
		return ($this->value)(...func_get_args());
	}

	/**
	 * Cast the value to a string.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return (string)$this->value;
	}
}
