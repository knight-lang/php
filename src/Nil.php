<?php
namespace Knight;

/**
 * The null type within Knight.
 *
 * Unlike every other `Value`, `Nil` doesn't take an argument in its constructor---this is because there is only one
 * kind of `Nil`.
 *
 * Note this class is named `Nil` because `Null` is not a valid class name.
 **/
class Nil extends Value
{
	/**
	 * Attempt to parse a Nil from the given stream.
	 *
	 * @param Stream $stream The stream to read from.
	 * @return ?self Returns the parsed Nil if it's able to be parsed, otherwise null.
	 **/
	public static function parse(Stream $stream): ?self
	{
		return is_null($stream->match('N[A-Z]*')) ? null : new self();
	}

	/**
	 * Converts this `Nil` to a string.
	 *
	 * @return string The empty string.
	 **/
	public function __toString(): string
	{
		return '';
	}

	/**
	 * Converts this `Nil` to an int.
	 *
	 * @return int Zero.
	 **/
	public function toInt(): int
	{
		return 0;
	}

	/**
	 * Converts this `Nil` to an boolean.
	 *
	 * @return bool False.
	 **/
	public function toBool(): bool
	{
		return false;
	}

	/**
	 * Converts this `Nil` to an array.
	 *
	 * @return array The empty array.
	 **/
	public function toArray(): array
	{
		return [];
	}

	/**
	 * Gets a string representation of this class
	 *
	 * @return string Literally `'null'`.
	 **/
	public function dump(): string
	{
		return 'null';
	}

	/**
	 * Checks to see if `$value` is a `Nil` and equal to `$this`.
	 *
	 * @param Value $value The value to compare against.
	 * @return bool
	 **/
	public function eql(Value $value): bool
	{
		return is_a($value, get_class($this));
	}
}
