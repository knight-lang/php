<?php
namespace Knight;

/**
 * The type that represents function, and its arguments, within Knight.
 **/
class Func extends Value
{
	/**
	 * The list of all known functions.
	 *
	 * Each function name must be a single character.
	 *
	 * @var array[string => [func, callable]]
	 **/
	private static array $KNOWN = [];

	/**
	 * Registers a new function, overwriting the previous one (if it existed).
	 *
	 * @param string $name The name of the function; this must be a single character in length.
	 * @param int $arity The amount of arguments the function takes.
	 * @param callable $func The function itself.
	 * @return void
	 * @throws Exception If the length of `$name` isn't exactly one, an exception is thrown.
	 **/
	public static function register(string $name, int $arity, callable $func): void
	{
		if (strlen($name) !== 1) {
			throw new \Exception('Name must be exactly one letter long');
		}

		Func::$KNOWN[$name] = [$func, $arity];
	}

	/**
	 * Parses a Func and its arguments from the stream, returning null if no function is found.
	 *
	 * @param Stream $stream The stream to read from.
	 * @return ?self Returns the parsed Func if it's able to be parsed, otherwise null.
	 * @throws Exception Thrown if an unknown function name is parsed.
	 * @throws Exception Thrown if an argument is missing from the function.
	 **/
	public static function parse(Stream $stream): ?self
	{
		$match = $stream->match('[A-Z]+|\S');

		if (is_null($match)) {
			return null;
		}

		$name = $match[0];

		[$func, $arity] = Func::$KNOWN[$name];

		if (!isset($func)) {
			throw new \Exception("unknown function '$name'");
		}

		$args = [];

		for ($i = 0; $i < $arity; $i++) {
			$next = Value::parse($stream);

			if (!isset($next)) {
				throw new \Exception("missing argument $i for function '$name'");
			}

			$args[] = $next;
		}

		return new self($func, $name, $args);
	}

	/**
	 * The function to call.
	 *
	 * @var callable
	 **/
	private $func;

	/**
	 * The arguments for this function.
	 *
	 * @var Value[]
	 **/
	private array $args;

	/**
	 * The name of this function; used for debugging.
	 *
	 * @var string
	 **/
	private string $name;

	/**
	 * Creates a new Func.
	 *
	 * This is `private`; to "create" a function, instead register it.
	 *
	 * @param callable $func The function associated with this instance.
	 * @param array $args The arguments for the function.
	 **/
	private function __construct(callable $func, string $name, array $args)
	{
		$this->func = $func;
		$this->name = $name;
		$this->args = $args;
	}

	/**
	 * Executes this function, and returns the result of it.
	 *
	 * While this function itself doesn't directly throw exceptions, `$func` may do so.
	 *
	 * @return Value The result of running the function.
	 **/
	public function run(): Value
	{
		return ($this->func)(...$this->args);
	}

	/**
	 * Runs this function, then converts the returned value to a string.
	 *
	 * @return string The result string representation of the result of running this function.
	 **/
	public function __toString(): string
	{
		return (string) $this->run();
	}

	/**
	 * Runs this function, then converts the returned value to an int.
	 *
	 * @return int The result int representation of the result of running this function.
	 **/
	public function toInt(): int
	{
		return $this->run()->toInt();
	}

	/**
	 * Runs this function, then converts the returned value to a bool.
	 *
	 * @return bool The result bool representation of the result of running this function.
	 **/
	public function toBool(): bool
	{
		return $this->run()->toBool();
	}

	/**
	 * Runs this function, then converts the returned value to an array.
	 *
	 * @return array The result array representation of the result of running this function.
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
		$ret = "Function('$this->name'";

		foreach ($this->args as $value) {
			$ret .= ", " . $value->dump();
		}

		return $ret . ')';
	}

	/**
	 * Checks to see if `$value` is identical to `$this`.
	 *
	 * @param Value $value The value to compare against.
	 * @return bool
	 **/
	public function eql(Value $value): bool
	{
		return $this === $value;
	}
}

/**
 * Gets a string from standard in, without removing the trailing newline.
 *
 * @return Value The string that's read from stdin.
 **/
Func::register('P', 0, function(): Value {
	$line = fgets(STDIN);

	if ($line === false) {
		return new Nil();
	}

	if (substr($line, strlen($line) - 1) == "\n") {
		$line = substr($line, 0, strlen($line) - 1);
	}

	if (substr($line, strlen($line) - 1) == "\r") {
		$line = substr($line, 0, strlen($line) - 1);
	}

	return new Str($line);
});

/**
 * Get a random number from 0-4294967295 (ie 0xffffffff), inclusive.
 *
 * @return Number The random number.
 **/
Func::register('R', 0, function(): Number {
	return new Number(rand(0, 0xffffffff));
});

/**
 * Interprets the parameter as Knight code and executes it.
 *
 * @param Value $text The value that will be converted to a string, and run.
 * @return Value The result of the executing the code.
 **/
Func::register('E', 1, function(Value $text): Value {
	return \Knight\run((string) $text->run());
});

/**
 * Simply returns the parameter unevaluated.
 *
 * This is used to delay execution of code. When used in conjunction with `C` (ie `CALL`), a very
 * basic form of functions can be implemented (albeit with no parameter passing.)
 *
 * @param Value $block The block to delay execution for.
 * @return Value Literally just returns `$block`.
 **/
Func::register('B', 1, function(Value $block): Value {
	return $block;
});

/**
 * Runs the passed argument twice.
 *
 * When used in conjunction with `B` (ie `BLOCK`), a very basic form of functions can be implemented
 * (albeit with no parameter passing).
 *
 * @param Value $block The vpiece of code to be executed twice.
 * @return Value The returned value of the second execution.
 **/
Func::register('C', 1, function(Value $block): Value {
	return $block->run()->run();
});

/**
 * Runs the passed argument as a shell command, returning the command's standard out.
 *
 * The return status of the command is ignored, and the standard error is not captured.
 *
 * @param Value $command The entire shell command to be run.
 * @return Str The standard out of the command.
 **/
Func::register('`', 1, function(Value $command): Str {
	return new Str(shell_exec($command->run()) ?: "");
});

/**
 * Stops execution with the given status code.
 *
 * @param Value $code The status code to exit with.
 * @return void
 **/
Func::register('Q', 1, function(Value $code): void {
	exit($code->toInt());
});

/**
 * Returns the logical negation of the argument.
 *
 * @param Value $arg The argument to array.
 * @return Boolean The negation of `$arg`.
 **/
Func::register('!', 1, function(Value $arg): Boolean {
	return new Boolean(!$arg->toBool());
});

/**
 * Returns the numeric negation of the argument.
 *
 * @param Value $arg The argument to negate.
 * @return Number The negation of `$arg`.
 **/
Func::register('~', 1, function(Value $arg): Number {
	return new Number(-$arg->toInt());
});

/**
 * Returns the `chr` or `ord` of its argument, depending on its type.
 *
 * @param Value $arg The argument to get the chr/ord of.
 * @return Value The chr/ord of the argument.
 **/
Func::register('A', 1, function(Value $arg): Value {
	return $arg->run()->ascii();
});

/**
 * Converts the argument into an array, then returns its length.
 *
 * @param Value $array The argument to array.
 * @return Number The length of `$array`
 **/
Func::register('L', 1, function(Value $array): Number {
	return new Number(count($array->toArray()));
});

/**
 * Dumps its argument to stdout, after executing it.
 *
 * @param Value $arg The argument to dump.
 * @return Value The result of `run`ning the argument.
 **/
Func::register('D', 1, function(Value $val): Value {
	$val = $val->run();
	echo $val->dump();
	return $val;
});

/**
 * Writes the message to stdout.
 *
 * Normally, a newline is printed at the end. However, If `$message` ends with a backslash, the
 * backslash will be stripped and the newline suppressed.
 *
 * @param Value $arg The value to print out.
 * @return Nil
 **/
Func::register('O', 1, function(Value $message): Nil {
	$string = (string) $message->run();

	if (substr($string, -1) === '\\') {
		echo substr($string, 0, -1);
	} else {
		echo $string, PHP_EOL;
	}

	return new Nil();
});

/**
 * Creates a new Ary with just its argument.
 *
 * @param Value $value The value to run and then insert into an array
 * @return Ary An array containing just `$value`.
 **/
Func::register(',', 1, function(Value $value): Ary {
	return new Ary([$value->run()]);
});

/**
 * Gets the first character/element of `$container`.
 *
 * @param Value $container The value to get the first character/element of
 * @return Value either a Str containing the first char of a Str, or the first element of a Ary.
 **/
Func::register('[', 1, function(Value $container): Value {
	return $container->run()->head();
});

/**
 * Gets everything but first character/element of `$container`.
 *
 * @param Value $container The value to get everything but first character/element of
 * @return Value Either a Str containing the everything but the first char, or an Ary containing
 *               everything but the first element.
 **/
Func::register(']', 1, function(Value $container): Value {
	return $container->run()->tail();
});

/**
 * Calls the `add` function on `$lhs` with `$rhs`'s value.
 *
 * @param Value $lhs
 * @param Value $rhs
 * @return Value The result of adding `$rhs` to `$lhs`.
 **/
Func::register('+', 2, function(Value $lhs, Value $rhs): Value {
	return $lhs->run()->add($rhs->run());
});

/**
 * Calls the `sub` function on `$lhs` with `$rhs`'s value.
 *
 * @param Value $lhs
 * @param Value $rhs
 * @return Value The result of subtracting `$rhs` from `$lhs`.
 **/
Func::register('-', 2, function(Value $lhs, Value $rhs): Value {
	return $lhs->run()->sub($rhs->run());
});

/**
 * Calls the `mul` function on `$lhs` with `$rhs`'s value.
 *
 * @param Value $lhs
 * @param Value $rhs
 * @return Value The result of multiplying `$lhs` by `$rhs`.
 **/
Func::register('*', 2, function(Value $lhs, Value $rhs): Value {
	return $lhs->run()->mul($rhs->run());
});

/**
 * Calls the `div` function on `$lhs` with `$rhs`'s value.
 *
 * @param Value $lhs
 * @param Value $rhs
 * @return Value The result of dividing `$lhs` by `$rhs`.
 **/
Func::register('/', 2, function(Value $lhs, Value $rhs): Value {
	return $lhs->run()->div($rhs->run());
});

/**
 * Calls the `mod` function on `$lhs` with `$rhs`'s value.
 *
 * @param Value $lhs
 * @param Value $rhs
 * @return Value The result of "moduloing" `$lhs` by `$rhs`.
 **/
Func::register('%', 2, function(Value $lhs, Value $rhs): Value {
	return $lhs->run()->mod($rhs->run());
});

/**
 * Calls the `pow` function on `$lhs` with `$rhs`'s value.
 *
 * @param Value $lhs
 * @param Value $rhs
 * @return Value The result of raising `$lhs` to the `$rhs`th power.
 **/
Func::register('^', 2, function(Value $lhs, Value $rhs): Value {
	return $lhs->run()->pow($rhs->run());
});

/**
 * Calls the `cmp` function on `$lhs` with `$rhs`'s value, and sees if it's less than 0.
 *
 * @param Value $lhs
 * @param Value $rhs
 * @return Boolean True if `$lhs` is less than `$rhs`, false otherwise.
 **/
Func::register('<', 2, function(Value $lhs, Value $rhs): Boolean {
	return new Boolean($lhs->run()->cmp($rhs->run()) < 0);
});

/**
 * Calls the `cmp` function on `$lhs` with `$rhs`'s value, and sees if it's less than 0.
 *
 * @param Value $lhs
 * @param Value $rhs
 * @return Boolean True if `$lhs` is greater than `$rhs`, false otherwise.
 **/
Func::register('>', 2, function(Value $lhs, Value $rhs): Boolean {
	return new Boolean($lhs->run()->cmp($rhs->run()) > 0);
});

/**
 * Checks to see if the two arguments are equal.
 *
 * @param Value $lhs
 * @param Value $rhs
 * @return Boolean True if `$lhs` is equal to `$rhs`, false otherwise.
 **/
Func::register('?', 2, function(Value $lhs, Value $rhs): Boolean {
	return new Boolean($lhs->run()->eql($rhs->run()));
});

/**
 * Returns `$lhs` if `$lhs` is falsey, otherwise returns `$rhs`.
 *
 * @param Value $lhs
 * @param Value $rhs
 * @return Value Returns either `$lhs` or `$rhs`, depending on whether `$lhs` is falsey or not.
 **/
Func::register('&', 2, function(Value $lhs, Value $rhs): Value {
	$lhs = $lhs->run();

	return $lhs->toBool() ? $rhs->run() : $lhs;
});

/**
 * Returns `$lhs` if `$lhs` is truthy, otherwise returns `$rhs`.
 *
 * @param Value $lhs
 * @param Value $rhs
 * @return Value Returns either `$lhs` or `$rhs`, depending on whether `$lhs` is truthy or not.
 **/
Func::register('|', 2, function(Value $lhs, Value $rhs): Value {
	$lhs = $lhs->run();

	return $lhs->toBool() ? $lhs : $rhs->run();
});

/**
 * Simply runs the first argument, then the second, returning the result of the second's execution.
 *
 * @param Value $lhs
 * @param Value $rhs
 * @return Value The result of executing `$rhs`.
 **/
Func::register(';', 2, function(Value $lhs, Value $rhs): Value {
	$lhs->run();
	return $rhs->run();
});

/**
 * Executes the body while the condition returns true.
 *
 * @param Value $cond The condition to run before each body execution.
 * @param Value $body The code to be run if the condition happens to be true.
 * @return Nil
 **/
Func::register('W', 2, function(Value $cond, Value $body): Nil {
	while ($cond->run()->toBool()) {
		$body->run();
	}

	return new Nil();
});

/**
 * Assigns a variable, globally.
 *
 * If `$var` is not an `Identifier`, it will be executed, converted to a string, and then into an
 * identifier. Any previous value is discarded.
 *
 * Note that all identifiers in Knight are global---there are no local variables.
 *
 * @param Value $var The value to assign to.
 * @param Value $val The value to set `$var` to.
 * @return Value The return value of executing `$val`.
 **/
Func::register('=', 2, function(Value $var, Value $val): Value {
	if (!is_a($var, '\Knight\Identifier')) {
		$var = new Identifier((string) $var->run());
	}

	$val = $val->run();
	$var->assign($val);
	return $val;
});

/**
 * Executes code depending on the condition.
 *
 * @param Value $cond The condition to check against.
 * @param Value $iftrue The code to execute if the condition is true.
 * @param Value $iffalse The code to execute if the condition is true.
 * @return Value The result of executing either `iftrue` or `iffalse`.
 **/
Func::register('I', 3, function(Value $cond, Value $iftrue, Value $iffalse): Value {
	return ($cond->run()->toBool() ? $iftrue : $iffalse)->run();
});

/**
 * Fetches a substring/array of a string/array.
 *
 * If `$start` is greater than `$container`'s length, an empty container is returned.
 * If `$length` is greater than `$container`'s length, it's assumed to be `$container`'s length.
 *
 * @param Value $container The container to fetch from.
 * @param Value $start The start of the "subcontainer".
 * @param Value $length The length of the "subcontainer".
 * @return Value The "subcontainer" specified.
 **/
Func::register('G', 3, function(Value $container, Value $start, Value $length): Value {
	return $container->run()->get($start->run(), $length->run());
});

/**
 * Returns a new Str/Ary with a specific range replaced by `$replacement`.
 *
 * If `$start` is greater than `$container`'s length, the "subcontainer" is appended to the end.
 * If `$length` is greater than `$container`'s length, it's assumed to be the length of the
 * container
 *
 * Note that this actually returns a new string; the original string is unmodified.
 *
 * @param Value $container The string to replace.
 * @param Value $start The start of the replacement region.
 * @param Value $length The length of the replacement region.
 * @param Value $repl The "subcontainer" to use when replacing.
 * @return Value The updated container.
 **/
Func::register('S', 4, function(Value $container, Value $start, Value $length, Value $repl): Value {
	return $container->run()->set($start->run(), $length->run(), $repl->run());
});
