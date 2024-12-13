<?php
namespace Knight;

/**
 * Identifiers within Knight.
 *
 * As per the specs for Knight, all variables are global scope.
 **/
class Identifier extends Value
{
	/**
	 * The list of all known identifiers and their associated values.
	 *
	 * @var array[string => Value]
	 **/
	private static array $VARIABLES = [];

	/**
	 * Attempt to parse an Identifier from the given stream.
	 *
	 * @param Stream $stream The stream to read from.
	 * @return ?self Returns the parsed Identifier if it's able to be parsed, otherwise null.
	 **/
	public static function parse(Stream $stream): ?self
	{
		$match = $stream->match('[a-z_][a-z_0-9]*');

		if (is_null($match)) {
			return null;
		}

		if (!array_key_exists($match, self::$VARIABLES)) {
			self::$VARIABLES[$match] = new self($match);
		}

		return self::$VARIABLES[$match];
	}

	/**
	 * This Identifier's name.
	 *
	 * @var string
	 **/
	private string $name;

	/**
	 * This Identifier's value.
	 *
	 * @var ?Value
	 **/
	private ?Value $value;

	/**
	 * Create a new Identifier with the given value.
	 *
	 * @param string $val The text of this identifier.
	 **/
	public function __construct(string $name)
	{
		$this->name = $name;
		$this->value = null;
	}

	/**
	 * Looks up this variable in the list of known variables, returning its most recently assigned value.
	 *
	 * @return Value The most recent value associated with this variable.
	 * @throws Exception Thrown if the variable has not been set yet.
	 **/
	public function run(): Value
	{
		if (is_null($this->value)) {
			throw new \Exception("unknown variable '$this->name'!");
		}

		return $this->value;
	}

	/**
	 * Assigns a value to this identifier.
	 *
	 * The previous value is discarded.
	 *
	 * @param Value $value The value to assign to this identifier.
	 * @return void
	 **/
	public function assign(Value $value): void
	{
		$this->value = $value;
	}

	/**
	 * Fetches this identifier's value, then converts it to a string.
	 *
	 * @return string The string representation of the value associated with this identifier.
	 * @throws Exception Thrown if the variable has not been set yet.
	 **/
	public function __toString(): string
	{
		return (string) $this->run();
	}

	/**
	 * Fetches this identifier's value, then converts it to an int.
	 *
	 * @return int The result of calling `toInt` on the value associated with this identifier.
	 * @throws Exception Thrown if the variable has not been set yet.
	 **/
	public function toInt(): int
	{
		return $this->run()->toInt();
	}

	/**
	 * Fetches this identifier's value, then converts it to a bool.
	 *
	 * @return bool The result of calling `toBool` on the value associated with this identifier.
	 * @throws Exception Thrown if the variable has not been set yet.
	 **/
	public function toBool(): bool
	{
		return $this->run()->toBool();
	}

	/**
	 * Fetches this identifier's value, then converts it to an array.
	 *
	 * @return bool The result of calling `toArray` on the value associated with this identifier.
	 * @throws Exception Thrown if the variable has not been set yet.
	 **/
	public function toArray(): array
	{
		return $this->run()->toArray();
	}

	/**
	 * Gets a string representation of this class
	 *
	 * @return string
	 **/
	public function dump(): string
	{
		return "Identifier($this->data)";
	}

	/**
	 * Checks to see if `$value` is identical to `$this`.
	 *
	 * @return bool
	 **/
	public function eql(Value $value): bool
	{
		return $this === $value;
	}
}
