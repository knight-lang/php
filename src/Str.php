<?php
namespace Knight;

/**
 * The string type within Knight.
 *
 * Note this class is named `Str` because `String` is not a valid class name.
 **/
class Str extends Value
{
	/**
	 * Attempt to parse a Str from the given stream.
	 *
	 * @param Stream $stream The stream to read from.
	 * @return ?self Returns the parsed Str if it's able to be parsed, otherwise null.
	 **/
	public static function parse(Stream $stream): ?self
	{
		$match = $stream->match("([\"'])((?:(?!\\1).|\n)*)\\1", 2);

		if (is_null($match)) {
			if ($stream->match("[\"']")) {
				throw new \Exception('Unterminated quote encountered!');
			} else {
				return null;
			}
		}

		return new self($match);
	}

	/**
	 * This Str's value.
	 *
	 * @var string
	 **/
	private string $data;

	/**
	 * Create a new Str with the given value.
	 *
	 * @param string $val The value of this Str.
	 **/
	public function __construct(string $val)
	{
		$this->data = $val;
	}

	/**
	 * Converts this Str to a string.
	 *
	 * @return bool Simply returns the data associated with this class.
	 **/
	public function __toString(): string
	{
		return $this->data;
	}

	/**
	 * Converts this Str to an int.
	 *
	 * @return int Converts to an int using PHP's conversion rules, which are similar to Knight's
	 **/
	public function toInt(): int
	{
		// Avoid php's scientific notation by manually grepping.
		if (!preg_match("/\A\s*[-+]?\d+/m", $this->data, $match)) {
			return 0;
		} else {
			return (int) $match[0];
		}
	}

	/**
	 * Converts this Str to an bool.
	 *
	 * @return bool An empty string is false; everything else (including `"0"` is considered true).
	 **/
	public function toBool(): bool
	{
		return $this->data !== '';
	}

	/**
	 * Gets a string representation of this class
	 *
	 * @return string
	 **/
	public function dump(): string
	{
		return '"' . addcslashes($this->data, "\r\n\t\"\\") . '"';
	}

	/**
	 * Converts this Str to an array.
	 *
	 * @return array An array of all the chars in the string.
	 **/
	public function toArray(): array
	{
		return array_map(fn($a) => new self($a), str_split($this->data));
	}

	/**
	 * Converts $rhs to a string and then adds it to the end of $this.
	 *
	 * @param Value $rhs The value concatenate to this.
	 * @return self `$this` concatenated with `$rhs` converted to a string.
	 **/
	public function add(Value $rhs): self
	{
		return new self($this . $rhs);
	}

	/**
	 * Converts $count to an int, then repeats $this that many times.
	 *
	 * For example, `new Str("ab")->mul(new Str("3"))` will return `ababab`. If `$count` is zero,
	 * then an empty string will be returned.
	 *
	 * @param Value $count The value by which `$this` will be duplicated.
	 * @return self `$this` duplicated `$count` times.
	 **/
	public function mul(Value $count): self
	{
		return new self(str_repeat($this, $count->toInt()));
	}

	/**
	 * Converts the $rhs to an string, then lexicographically compares $this to it.
	 *
	 * @param Value $rhs The string by which `$this` will be raised.
	 * @return int Returns a number less than, equal to, or greater than 0, depending on if `$rhs`,
	 *             after conversion to an int, is less than, equal to, or greater than `$this`.
	 **/
	public function cmp(Value $rhs): int
	{
		return strcmp($this, $rhs);
	}

	/**
	 * Checks to see if `$value` is a `Str` and equal to `$this`.
	 *
	 * @param Value $value The value to compare against.
	 * @return bool
	 **/
	public function eql(Value $value): bool
	{
		return is_a($value, get_class($this)) && $this->data === $value->data;
	}

	/**
	 * Returns the first character of the Str. Throws an exception if the string is empty.
	 *
	 * @return self
	 **/
	public function head(): self
	{
		if (!strlen($this->data)) {
			throw new \Exception('head on empty str');
		}

		return new self(substr($this->data, 0, 1));
	}

	/**
	 * Returns a string of everything but the first char. Throws an exception if the string is empty.
	 *
	 * @return self
	 **/
	public function tail(): self
	{
		if (!strlen($this->data)) {
			throw new \Exception('tail on empty str');
		}

		return new self(substr($this->data, 1));
	}

	/**
	 * Returns the codepoint corresponding to the first character of the string.
	 *
	 * @return Number A number containing the codepoint.
	 **/
	public function ascii(): Number
	{
		return new Number(ord($this->data));
	}

	/**
	 * Gets the substring `[$start..$start + $length)` and returns a new Str containing it.
	 *
	 * @param Value $start The starting position.
	 * @param Value $length The total amount of characters in the substring.
	 * @return Str The substring.
	 **/
	public function get(Value $start, Value $length): self
	{
		return new self(substr($this->data, $start->toInt(), $length->toInt()));
	}

	/**
	 * Replaces the range `[$start..$start + $length)` within `$this` with `$replacement` and returns
	 * the resulting Str.
	 *
	 * This doesn't modify `$this`.
	 *
	 * @param Value $start The starting position.
	 * @param Value $length The total amount of characters in the substring.
	 * @param Value $replacement The value to replace the range with.
	 * @return Ary Str resulting string.
	 **/
	public function set(Value $start, Value $length, Value $replacement): self
	{
		return new self(substr_replace($this->data, $replacement, $start->toInt(), $length->toInt()));
	}
}
