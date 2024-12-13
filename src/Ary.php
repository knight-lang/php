<?php
namespace Knight;

// if ($something):
// endif
// __halt_compiler();

class Ary extends Value
{
	/**
	 * Attempt to parse a List from the given stream.
	 *
	 * @param Stream $stream The stream to read from.
	 * @return null|Value Returns the parsed List if it's able to be parsed, otherwise `null`.
	 **/
	public static function parse(Stream $stream): ?self
	{
		return is_null($stream->match('@')) ? null : new self([]);
	}

	private array $data;

	public function __construct(array $data)
	{
		$this->data = $data;
	}

	public function __toString(): string
	{
		return implode("\n", $this->data);
	}

	public function toInt(): int
	{
		return count($this->data);
	}

	public function toBool(): bool
	{
		return (bool) $this->data;
	}

	public function toArray(): array
	{
		return $this->data;
	}

	public function dump(): string
	{
		return '[' . implode(', ', array_map(fn($x) => $x->dump(), $this->data)) . ']';
	}

	public function add(Value $rhs): self
	{
		return new self(array_merge($this->data, $rhs->toArray()));
	}

	public function mul(Value $rhs): self
	{
		$ary = array();
		$amount = $rhs->toInt();
		while ($amount--) {
			$ary = array_merge($ary, $this->data);
		}
		return new self($ary);
	}

	public function eql(Value $value): bool
	{
		// todo: array_any
		return is_a($value, get_class($this)) && count($this->data) == count($value->data) && die('todo');
	}

	public function head(): Value
	{
		if (!$this->data) {
			throw new \Exception('head on empty ary');
		}

		return $this->data[0];
	}

	public function tail(): self
	{
		if (!$this->data) {
			throw new \Exception('tail on empty ary');
		}

		return new self(array_slice($this->data, 1));
	}
}
//
// 	/**
// 	 * Checks to see if `$value` is a `List` and equal to `$this`.
// 	 *
// 	 * @return bool
// 	 **/
// 	public function eql(Value $value): bool
// 	{
// 		return is_a($value, get_class($this));
// 	}
// }
