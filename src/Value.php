<?php
namespace Knight;

/**
 * The base class for all things that are representable within Knight.
 **/
abstract class Value
{
	/**
	 * All the different types that exist within Knight.
	 *
	 * @var class[]
	 **/
	private const array TYPES = [
		Identifier::class,
		Number::class,
		Str::class,
		Boolean::class,
		Nil::class,
		Ary::class,
		Func::class
	];

	/**
	 * Attempts to parse a Value from the given Stream.
	 *
	 * If a value is found, the stream will be updated accordingly; if nothing can be parsed, `null`
	 * will be returned.
	 *
	 * @param Stream $stream The stream which will be parsed from.
	 * @return ?self Returns the parsed Value, or null if nothing could be parsed.
	 **/
	public static function parse(Stream $stream): ?self
	{
		$stream->strip();

		foreach (self::TYPES as $class) {
			if (!is_null($value = $class::parse($stream))) {
				return $value;
			}
		}

		return null;
	}

	/**
	 * Converts this value to a int.
	 *
	 * @return int
	 **/
	abstract public function toInt(): int;

	/**
	 * Converts this value to a bool.
	 *
	 * @return bool
	 **/
	abstract public function toBool(): bool;

	/**
	 * Converts this value to an array.
	 *
	 * @return array
	 **/
	abstract public function toArray(): array;

	/**
	 * Gets a string representation of this class
	 *
	 * @return string
	 **/
	abstract public function dump(): string;

	/**
	 * Checks to see if `$this` is equal to `$value`.
	 *
	 * @param Value $value The value to compare against.
	 * @return bool
	 **/
	abstract public function eql(Value $value): bool;

	/**
	 * Executes this Value.
	 *
	 * By default, the return value is simply `$this`.
	 *
	 * @return Value The result of running this value.
	 **/
	public function run(): Value
	{
		return $this;
	}
}
