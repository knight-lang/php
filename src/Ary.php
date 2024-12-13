<?php
namespace Knight;

/**
 * The array type within Knight.
 *
 * We have to name it `Ary` as `Array` is already taken by PHP :-(.
 **/
class Ary extends Value
{
	/**
	 * Attempt to parse an Ary from the given stream.
	 *
	 * @param Stream $stream The stream to read from.
	 * @return ?self Returns the parsed Ary if it's able to be parsed, otherwise null.
	 **/
	public static function parse(Stream $stream): ?self
	{
		return is_null($stream->match('@')) ? null : new self([]);
	}

	/**
	 * This Ary's value.
	 *
	 * @var bool
	 **/
	private array $data;

	/**
	 * Create a new Ary with the given arguments.
	 *
	 * @param array $val The value of this array.
	 **/
	public function __construct(array $data)
	{
		$this->data = $data;
	}

	/**
	 * Converts this Ary to a string.
	 *
	 * @return string The data joined with a newline
	 **/
	public function __toString(): string
	{
		return implode("\n", $this->data);
	}

	/**
	 * Converts this Ary to an int.
	 *
	 * @return int The amount of elements in the array.
	 **/
	public function toInt(): int
	{
		return count($this->data);
	}

	/**
	 * Converts this Ary to a bool.
	 *
	 * @return bool Whether the array is nonempty.
	 **/
	public function toBool(): bool
	{
		return (bool) $this->data;
	}

	/**
	 * Converts this Ary to an array.
	 *
	 * @return array The underlying array.
	 **/
	public function toArray(): array
	{
		return $this->data;
	}

	/**
	 * Gets a string representation of this class.
	 *
	 * @return string
	 **/
	public function dump(): string
	{
		return '[' . implode(', ', array_map(fn($x) => $x->dump(), $this->data)) . ']';
	}

	/**
	 * Returns a new Ary with `$this` merged with `$rhs->toArray()`.
	 *
	 * @param Value $rhs The array to merge onto the end of `$this`.
	 * @return self The result of the merge.
	 **/
	public function add(Value $rhs): self
	{
		return new self(array_merge($this->data, $rhs->toArray()));
	}

	/**
	 * Returns a new Ary with `$this` repeated `$amount` times.
	 *
	 * @param Value $amount The amount of times to repeat the array.
	 * @return self The result of the repetition.
	 **/
	public function mul(Value $amount): self
	{
		$result = array();
		$amount = $amount->toInt();

		while ($amount--) {
			$result = array_merge($result, $this->data);
		}

		return new self($result);
	}

	/**
	 * Checks to see if `$value` is an Ary and equal to `$this`.
	 *
	 * @param Value $value The value to compare against.
	 * @return bool
	 **/
	public function eql(Value $value): bool
	{
		// NOTE: We can't use `array_any` as I'm not using PHP 8.3, and array_any was added in 8.4

		if (!is_a($value, get_class($this)) || count($this->data) != count($value->data)) {
			return false;
		}

		for ($i = 0; $i < count($this->data); ++$i) {
			if (!$this->data[$i]->eql($value->data[$i])) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Compares `$this` against `$value->toArray()`, according to the Knight specs.
	 *
	 * @param Value $value The value to compare against.
	 * @return int negative when smaller, 0 when equal, and positive when larger.
	 **/
	public function cmp(Value $other): int
	{
		$other = $other->toArray();

		$min = min(count($other), count($this->data));

		for ($i = 0; $i < $min; ++$i) {
			if (($cmp = $this->data[$i]->cmp($other[$i]))) {
				return $cmp;
			}
		}

		return count($this->data) - count($other);
	}

	/**
	 * Returns the first element of the array. Throws an exception if the array is empty.
	 *
	 * @return Value
	 **/
	public function head(): Value
	{
		if (!$this->data) {
			throw new \Exception('head on empty ary');
		}

		return $this->data[0];
	}

	/**
	 * Returns an array of everything but the first element. Throws an exception if the array is
	 * empty.
	 *
	 * @return self
	 **/
	public function tail(): self
	{
		if (!$this->data) {
			throw new \Exception('tail on empty ary');
		}

		return new self(array_slice($this->data, 1));
	}

	/**
	 * Joins this array by `$sep`.
	 *
	 * @param Value $sep The separator
	 * @return Str The resulting string
	 **/
	public function pow(Value $sep): Str
	{
		return new Str(implode($sep, $this->data));
	}

	/**
	 * Gets the range `[$start..$start + $length)` and returns a new Ary containing it.
	 *
	 * @param Value $start The starting position.
	 * @param Value $length The total amount of elements to use.
	 * @return Ary The subslice.
	 **/
	public function get(Value $start, Value $length): self
	{
		return new self(array_slice($this->data, $start->toInt(), $length->toInt()));
	}

	/**
	 * Replaces the range `[$start..$start + $length)` within `$this` with `$replacement` and returns
	 * the resulting array.
	 *
	 * This doesn't modify `$this`.
	 *
	 * @param Value $start The starting position.
	 * @param Value $length The total amount of elements to use.
	 * @param Value $replacement The value to replace the range with.
	 * @return Ary The resulting array.
	 **/
	public function set(Value $start, Value $length, Value $replacement): self
	{
		$ary = array_merge($this->data);
		array_splice($ary, $start->toInt(), $length->toInt(), $replacement->toArray());
		return new self($ary);
	}
}
