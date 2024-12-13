<?php
namespace Knight;

/**
 * A Stream that's used when parsing Values.
 **/
class Stream
{
	/**
	 * The source of text that's to be parsed.
	 *
	 * @var string
	 **/
	private string $source;

	/**
	 * Creates a new Stream for the given $source
	 *
	 * @var string $source The string to use as the source for this Stream.
	 **/
	public function __construct(string $source)
	{
		$this->source = $source;
	}

	/**
	 * Removes all leading whitespace and comments.
	 *
	 * Note that, for Knight, round parens (ie `(` and `)`), as well as the colon (`:`) are
	 * considered whitespace.
	 *
	 * @return void
	 **/
	public function strip(): void
	{
		$this->source = preg_replace('/\A(?:[\s():]+|\#[^\n]*(\n|$))*/m', '', $this->source);
	}

	/**
	 * Attempts to match the $regex at the start of the source.
	 *
	 * If the regex matches, the entire matching string will be returned by default. The `$idx`
	 * parameter can be used to change this behaviour around.
	 *
	 * @param string $regex The regex to match against at the start. Implicitly has `/m` added.
	 * @param int $idx The index of the group to return; defaults to `0`, ie the entire match.
	 * @return ?string Returns the matching string/`$idx` capture group if `$regex` matched.
	 **/
	public function match(string $regex, int $idx=0): ?string
	{
		if (!preg_match("/\A(?:$regex)/m", $this->source, $match)) {
			return null;
		}

		$this->source = substr($this->source, strlen($match[0]));

		return $match[$idx];
	}
}
