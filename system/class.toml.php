<?php
namespace Toml;

/**
* A TOML parser for PHP
*/
class Parser
{
	protected $raw;
	protected $doc = array();
	protected $group;
	protected $lineNum = 1;

	public function __construct($raw)
	{
		$this->raw = $raw;
		$this->group = &$this->doc;
	}

	static public function fromString($s)
	{
		$parser = new self($s);

		return $parser->parse();
	}

	static public function fromFile($path)
	{
		if(!is_file($path) || !is_readable($path)) {
			throw new \RuntimeException(sprintf('`%s` does not exist or cannot be read.', $path));
		}

		return self::fromString(file_get_contents($path));
	}

	public function parse()
	{
		$inString   = false;
		$arrayDepth = 0;
		$inComment  = false;
		$buffer     = '';

		// Loop over each character in the file, each line gets built up in $buffer
		// We can't simple explode on newlines because arrays can be declared
		// over multiple lines.
		for($i = 0; $i < strlen($this->raw); $i++) {
			$char = $this->raw[$i];

			// Detect start of comments
			if($char === '#' && !$inString) {
				$inComment = true;
			}

			// Detect start / end of string boundries
			if($char === '"' && $this->raw[$i-1] !== '\\') {
				$inString = !$inString;
			}

			if($char === '[' && !$inString) {
				$arrayDepth++;
			}

			if($char === ']' && !$inString) {
				$arrayDepth--;
			}

			// At a line break or the end of the document see whats going on
			if($char === "\n") {
				$this->lineNum++;
				$inComment = false;

				// Line breaks arent allowed inside strings
				if($inString) {
					throw new \Exception('Multiline strings are not supported.');
				}

				if($arrayDepth === 0) {
					$this->processLine($buffer);
					$buffer = '';
					continue;
				}
			}

			// Don't append to the buffer if we're inside a comment
			if($inComment) {
				continue;
			}

			$buffer.= $char;
		}

		if($arrayDepth > 0) {
			throw new \Exception(sprintf('Unclosed array on line %s', $this->lineNum));
		}

		// Process any straggling content left in the buffer
		$this->processLine($buffer);

		return $this->doc;
	}

	protected function processLine($raw)
	{
		// replace new lines with a space to make parsing easier down the line.
		$line = str_replace("\n", ' ', $raw);
		$line = trim($line);

		// Skip blank lines
		if(empty($line)) {
			return;
		}

		// Check for groups
		if(preg_match('/^\[([^\]]+)\]$/', $line, $matches)) {
			$this->setGroup($matches[1]);
			return;
		}

		// Look for keys
		if(preg_match('/^(\S+)\s*=\s*(.+)/u', $line, $matches)) {
			$this->group[$matches[1]] = $this->parseValue($matches[2]);
			return;
		}

		throw new \Exception(sprintf('Invalid TOML syntax `%s` on line %s.', $raw, $this->lineNum));
	}

	protected function setGroup($keyGroup)
	{
		$parts = explode('.', $keyGroup);

		$this->group = &$this->doc;
		foreach($parts as $part) {
			if(!isset($this->group[$part])) {
				$this->group[$part] = array();
			} elseif(!is_array($this->group[$part])) {
				throw new \Exception(sprintf('%s has already been defined.', $keyGroup));
			}

			$this->group = &$this->group[$part];
		}
	}

	protected function parseValue($value)
	{
		// Detect bools
		if($value === 'true' || $value === 'false') {
			return $value === 'true';
		}

		// Detect floats
		if(preg_match('/^\-?\d+\.\d+$/', $value)) {
			return (float)$value;
		}

		// Detect integers
		if(preg_match('/^\-?\d*?$/', $value)) {
			return (int)$value;
		}

		// Detect string
		if(preg_match('/^"(.*)"$/u', $value, $matches)) {
			return $this->parseString($value);
		}

		// Detect datetime
		if(preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})Z$/', $value)) {
			return new \Datetime($value);
		}

		// Detect arrays
		if(preg_match('/^\[(.*)\]$/u', $value)) {
			return $this->parseArray($value);
		}

		throw new \Exception(sprintf('Unknown primative for `%s` on line %s.', $value, $this->lineNum));
	}

	protected function parseString($string)
	{
		$string = trim($string, '"');

		$allowedEscapes = implode('|', array(
			'\\\\0',
			'\\\\t',
			'\\\\n',
			'\\\\r',
			'\\\\"',
			'\\\\\\\\',
			'\\\\u[0-9A-Fa-f]{4}',
		));

		// Check for invalid escape codes by removing valid ones and looking for backslash character
		// This negates any complex regex to detect two (or more) adjoining back slash escape sequences
		$check = preg_replace('/'.$allowedEscapes.'/ums', '', $string);

		if(false !== strpos($check, '\\')) {
			throw new \Exception(sprintf('Invalid escape sequence on line %s', $this->lineNum));
		}

		return (string)json_decode('"'.$string.'"');
	}

	protected function parseArray($array)
	{
		// strips the outer wrapping [ and ] characters and and whitespace from the strip
		$array = preg_replace('/^\s*\[\s*(.*)\s*\]\s*$/usm', "$1", $array);

		$depth            = 0;
		$buffer           = '';
		$result           = array();
		$insideString     = false;
		$insideComment    = false;

		// TODO: This is a 80% duplicate of the logic in the parse() method.
		// Find a way to combine these blocks
		for($i = 0; $i < strlen($array); $i++) {

			if(!$insideString && $array[$i] === '[') {
				$depth++;
			}

			if(!$insideString && $array[$i] === ']') {
				$depth--;
			}

			if($array[$i] === '"' && ((isset($array[$i-1]) && $array[$i-1] !== '\\') || $i === 0))  {
				$insideString = !$insideString;
			}

			if(!$insideString && $array[$i] === '#') {
				$insideComment = true;
			}

			if(!$insideString && $array[$i] === ',' && 0 === $depth) {
				$result[] = $this->parseValue(trim($buffer));
				$this->validateArrayElementTypes($result);
				$buffer = '';
				continue;
			}

			if($array[$i] === "\n") {
				$insideComment = false;
			}

			if($insideComment === true) {
				continue;
			}

			$buffer.= $array[$i];
		}

		// Detect if array hasnt been closed properly
		if(0 !== $depth) {
			throw new \Exception(sprintf('Unclosed array on line %s', $this->lineNum));
		}

		// whatever meaningful text left in the buffer should be the last element
		if($buffer = trim($buffer)) {
			$result[] = $this->parseValue($buffer);
			$this->validateArrayElementTypes($result);
		}

		return $result;
	}

	protected function validateArrayElementTypes($array)
	{
		if(count($array) < 2) {
			return;
		}

		// Check the last two elements match in type (and classname if they are objects)
		// TODO: Tidy this up
		$indexA = count($array) - 2;
		$indexB = count($array) - 1;
		$typeA = gettype($array[$indexA]) === 'object' ? get_class($array[$indexA]) : gettype($array[$indexA]);
		$typeB = gettype($array[$indexB]) === 'object' ? get_class($array[$indexB]) : gettype($array[$indexB]);

		if($typeA !== $typeB) {
			throw new \Exception(sprintf('Arrays cannot contain mixed types on line %s', $this->lineNum));
		}
	}
}
