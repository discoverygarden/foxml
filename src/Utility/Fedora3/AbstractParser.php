<?php

namespace Drupal\foxml\Utility\Fedora3;

/**
 * Abstract (FO)XML parser.
 */
abstract class AbstractParser implements ParserInterface {
  const MAP = [];

  /**
   * Memoized MAP with "foxml:" expanded to the full URI.
   *
   * @var string[]
   */
  protected ?array $map = NULL;

  /**
   * The parser instance parsing.
   *
   * @var \Drupal\foxml\Utility\FoxmlParser|null
   */
  protected ?FoxmlParser $foxmlParser;

  /**
   * Parser state for this element; the depths of each tag.
   *
   * @var int[]
   */
  protected array $depths = [];

  /**
   * Parser state for this element; the stack of elements.
   *
   * @var \Drupal\foxml\Utility\Fedora3\ParserInterface[]
   */
  protected array $stack = [];

  /**
   * Associative array of attributes on this element.
   *
   * @var string[]
   */
  protected array $attributes;

  /**
   * Get the current element being processed.
   *
   * @return \Drupal\foxml\Utility\Fedora3\ParserInterface
   *   The current element being processed.
   */
  protected function current() : ParserInterface {
    return end($this->stack);
  }

  /**
   * Push another element onto the stack being parsed.
   *
   * @param \Drupal\foxml\Utility\Fedora3\ParserInterface $new
   *   The new element.
   */
  protected function push(ParserInterface $new) : void {
    $this->stack[] = $new;
  }

  /**
   * Pop the finished element off of the stack.
   *
   * @return \Drupal\foxml\Utility\Fedora3\ParserInterface
   *   A fully parsed element.
   */
  protected function pop() : ParserInterface {
    $old = array_pop($this->stack);
    $old->close();
    return $old;
  }

  /**
   * Constructor.
   *
   * @param \Drupal\foxml\Utility\Fedora3\FoxmlParser $foxml_parser
   *   The root parser parsing.
   * @param string[] $attributes
   *   The attributes for the given element.
   */
  public function __construct(FoxmlParser $foxml_parser, array $attributes) {
    $this->foxmlParser = $foxml_parser;
    $this->attributes = $attributes;
  }

  /**
   * Destructor; clean up.
   */
  public function __destruct() {
    $this->close();
  }

  /**
   * Get the root parser.
   *
   * @return \Drupal\foxml\Utility\Fedora3\FoxmlParser
   *   The root parser... essentially so XmlContent and BinaryContent can
   *   determine their offsets.
   */
  protected function getFoxmlParser() : ParserInterface {
    return $this->foxmlParser;
  }

  /**
   * Get the target attribute.
   *
   * @return string
   *   The value of the attribute.
   */
  public function __get($offset) {
    return $this->attributes[$offset];
  }

  /**
   * Check if the given attribute exists.
   *
   * @return bool
   *   TRUE if it exists; otherwise, FALSE.
   */
  public function __isset($offset) : bool {
    return isset($this->attributes[$offset]);
  }

  /**
   * Serialization descriptor.
   *
   * We do not care about trying to serialize everything on the object, so
   * let's specify a more restricted set of things.
   *
   * XXX: Ideally, we would have something separate from the parser classes
   * to represent the parsed structures; however... things are already together
   * ... could possibly separate the concerns of the parsing and the structure
   * in the future?
   *
   * @return string[]
   *   The object members to be serialized.
   */
  public function __sleep() : array {
    return ['attributes'];
  }

  /**
   * Clean up when the element is finished.
   */
  public function close() {
    // XXX: Attempt to avoid circular references, just in case.
    unset($this->foxmlParser);
    $this->depths = [];
    $this->stack = [];
  }

  /**
   * Get the map of tags to class names.
   *
   * @return string[]
   *   An associative array mapping the tag names provided by the parser to the
   *   names of the classes which should handle them.
   */
  protected function map() : array {
    if ($this->map === NULL) {
      $this->map = array_combine(
        array_map(function ($key) {
          [$prefix, $name] = explode(':', $key);
          if ($prefix === 'foxml') {
            $key = "info:fedora/fedora-system:def/foxml#:{$name}";
          }
          return $key;
        }, array_keys(static::MAP)),
        static::MAP
      );
    }
    return $this->map;
  }

  /**
   * {@inheritdoc}
   */
  public function tagOpen($parser, $tag, array $attributes) : void {
    if (isset($this->map()[$tag])) {
      if (!isset($this->depths[$tag])) {
        $this->depths[$tag] = 1;
      }
      else {
        $this->depths[$tag]++;
      }
      if ($this->depths[$tag] === 1) {
        $class = $this->map()[$tag];
        assert(is_a($class, ParserInterface::class, TRUE), 'Class is a parser.');
        $this->push(new $class($this->getFoxmlParser(), $attributes));
      }
      else {
        $this->current()->tagOpen($parser, $tag, $attributes);
      }
    }
    else {
      assert($this->current(), 'Deferred parsing to existing element.');
      $this->current()->tagOpen($parser, $tag, $attributes);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function tagClose($parser, $tag) : void {
    if (isset($this->map()[$tag])) {
      $this->depths[$tag]--;
      if ($this->depths[$tag] === 0) {
        $this->pop();
        unset($this->depths[$tag]);
      }
      else {
        assert($this->current(), 'Closing has element.');
        $this->current()->tagClose($parser, $tag);
      }
    }
    else {
      assert($this->current(), 'Deferred closing has element.');
      $this->current()->tagClose($parser, $tag);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function characters($parser, $chars) : void {
    if ($this->current()) {
      $this->current()->characters($parser, $chars);
    }
    else {
      // XXX: Characters are suppressed.
    }
  }

}
