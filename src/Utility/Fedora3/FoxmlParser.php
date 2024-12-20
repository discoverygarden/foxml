<?php

namespace Drupal\foxml\Utility\Fedora3;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\foxml\Utility\Fedora3\Element\DigitalObject;

/**
 * Foxml parser.
 */
class FoxmlParser extends AbstractParser {

  const READ_SIZE = 2 ** 18;

  /**
   * The parser instance.
   *
   * @var resource
   */
  protected $parser;

  /**
   * The path/URI of the target document being parsed.
   *
   * @var string
   */
  protected $target;

  /**
   * File pointer of the file being parsed.
   *
   * @var resource
   */
  protected $file = NULL;

  /**
   * A chunk of the input to feed to the parser.
   *
   * @var string
   */
  protected $chunk = NULL;

  /**
   * The parsed document.
   *
   * @var \Drupal\foxml\Utility\Fedora3\Element\DigitalObject
   */
  protected $output = NULL;

  /**
   * A cache to use for parse results.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Datastream storage service.
   *
   * @var \Drupal\foxml\Utility\Fedora3\DatastreamLowLevelAdapterInterface
   */
  protected $datastreamStorage;

  /**
   * Flag if datastream storage service is valid.
   *
   * @var bool|null
   */
  protected $validDatastreamStorage = NULL;

  /**
   * Semaphore/lock service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected LockBackendInterface $lock;

  const MAP = [
    DigitalObject::TAG => DigitalObject::class,
  ];

  /**
   * Constructor.
   */
  public function __construct(
    CacheBackendInterface $cache,
    LowLevelAdapterInterface $datastream_storage,
    LockBackendInterface $lock,
  ) {
    $this->cache = $cache;
    $this->datastreamStorage = $datastream_storage;
    $this->lock = $lock;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFoxmlParser() {
    return $this;
  }

  /**
   * Setup the parser.
   */
  protected function initParser() {
    $this->parser = xml_parser_create_ns();
    xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, FALSE);
    xml_parser_set_option($this->parser, XML_OPTION_SKIP_WHITE, TRUE);
    xml_set_object($this->parser, $this);
    xml_set_element_handler($this->parser, 'tagOpen', 'tagClose');
    xml_set_character_data_handler($this->parser, 'characters');
  }

  /**
   * {@inheritdoc}
   */
  public function close() {
    if ($this->file) {
      fclose($this->file);
    }
    $this->file = NULL;
    $this->chunk = NULL;
    $this->target = NULL;
    $this->output = NULL;
    $this->destroyParser();
    parent::close();
  }

  /**
   * Teardown the parser.
   */
  protected function destroyParser() {
    if ($this->parser) {
      if (!xml_parser_free($this->parser)) {
        throw new \Exception('Failed to free parser.');
      }
      $this->parser = NULL;
    }
  }

  /**
   * Get the datastream low-level adapter.
   *
   * @return \Drupal\foxml\Utility\Fedora3\DatastreamLowLevelAdapterInterface
   *   The adapter, if configured.
   *
   * @throws \Exception
   *   If there is no storage configured.
   */
  public function getDatastreamLowLevelAdapter() : DatastreamLowLevelAdapterInterface {
    if (is_null($this->validDatastreamStorage)) {
      $this->validDatastreamStorage = $this->datastreamStorage->valid();
    }
    if (!$this->validDatastreamStorage) {
      throw new \Exception('Invalid low-level datastream storage adapter configuration.');
    }

    return $this->datastreamStorage;
  }

  /**
   * Helper; build out the name of a lock to use.
   *
   * @param string $target
   *   The target for which to obtain a lock.
   *
   * @return string
   *   The name of the lock to use. Note: The name of the lock actually used by
   *   the back end may be different due to size constraints.
   */
  protected static function lockName(string $target) : string {
    return "foxml__parser_lock__{$target}";
  }

  /**
   * Get a parse of the target document.
   *
   * @param string $target
   *   A path/URL of a FOXML document to parse.
   * @param bool $control_concurrency
   *   Attempt to avoid having multiple processes concurrently parsing the exact
   *   same file.
   *
   * @return \Drupal\foxml\Utility\Fedora3\Element\DigitalObject
   *   The parsed document.
   */
  public function parse($target, bool $control_concurrency = FALSE) {
    $item = $this->cache->get($target);
    if ($item && $item->data) {
      // XXX: Renew the cache.
      $this->cache->set(
        $target,
        $item->data,
        // XXX: Keep things a week.
        time() + (3600 * 24 * 7)
      );
      return $item->data;
    }

    if ($control_concurrency) {
      $lock_name = static::lockName($target);
      $got_lock = FALSE;
      try {
        // Attempt to acquire the lock.
        $got_lock = $this->lock->acquire($lock_name, 600);

        // If we did not get the lock, wait until it might have been released.
        if (!$got_lock) {
          $this->lock->wait($lock_name, 600);
        }

        // Proceed to do the parsing independent of concurrency control, which
        // should:
        // - having obtained the lock, proceed to parse and populate the cache
        // - having waited for the lock, fetch the parsed results from the cache
        //   upon re-entry; or,
        // - having waited for the lock, fail to fetch the results from the
        //   cache and proceed and attempt to parse ourselves should something
        //   have prevented the original holder from populating the cache.
        $result = $this->parse($target, FALSE);
        if ($got_lock) {
          $this->lock->release($lock_name);
          $got_lock = FALSE;
        }

        return $result;
      }
      finally {
        // If still have the lock somehow, let it go.
        if ($got_lock) {
          $this->lock->release($lock_name);
        }
      }
    }

    $parsed = $this->doParse($target);

    $this->cache->set(
      $target,
      $parsed,
      // XXX: Keep things a week.
      time() + (3600 * 24 * 7)
    );

    return $parsed;
  }

  /**
   * Heavy lifting of doing the parsing.
   *
   * @param string $target
   *   A path/URL of a FOXML document to parse.
   *
   * @return \Drupal\foxml\Utility\Fedora3\Element\DigitalObject
   *   The parsed document.
   *
   * @throws \Drupal\foxml\Utility\Fedora3\FoxmlParserException
   *   Thrown when the XML parser encounters an error when parsing the FOXML.
   * @throws \Exception
   *   Thrown when we fail to obtain a DigitalObject from parsing.
   */
  protected function doParse($target) {

    $this->target = $target;

    $this->file = fopen($target, 'rb');

    try {
      if (!$this->file) {
        throw new \Exception('Failed to open file...');
      }
      $this->initParser();
      while (!feof($this->file)) {
        $this->chunk = fread($this->file, static::READ_SIZE);
        $result = xml_parse($this->parser, $this->chunk, feof($this->file));
        if ($result === 0 || xml_get_error_code($this->parser) !== XML_ERROR_NONE) {
          throw new FoxmlParserException($this->parser);
        }
      }

      if (!$this->output instanceof DigitalObject) {
        throw new \Exception("Parsing did not produce a DigitalObject class; truncated/bad file?");
      }

      return $this->output;
    }
    finally {
      $this->close();
    }
  }

  /**
   * Accessor for the target file path/URI.
   *
   * @return string
   *   The target file path/URI.
   */
  public function getTarget() {
    return $this->target;
  }

  /**
   * Get the current byte offset in from parsing the target document.
   *
   * @return int
   *   The current byte offset.
   */
  public function getOffset() {
    // XXX: Apparently, there may be differences in what
    // xml_get_current_byte_index() returns, based on what parser is used
    // (libxml2 vs expat); for example, "start element" having placed the offset
    // _after_ the started element for libxml2, while expat does not... somewhat
    // anecdotal, but something of which to be wary:
    // @see https://www.php.net/manual/en/function.xml-get-current-byte-index.php#56953
    // XXX: The type of the returned value may be a signed 32-bit integer,
    // leading to overflow with files larger than ~2GB... so let's adjust
    // accordingly.
    $pos = ftell($this->file);
    $index = xml_get_current_byte_index($this->parser);
    if ($index > 2 ** 31) {
      // Using a parser which does not need adjusting?
      return $index;
    }
    elseif ($index < 0) {
      // Is negative, definitely wrapped.
      return static::correctOffset($index, $pos);
    }
    elseif ($index >= 0 && $index < 2 ** 31 && $pos >= 2 ** 31) {
      // Positive, but wrapping.
      return static::correctOffset($index, $pos);
    }
    else {
      return $index;
    }
  }

  /**
   * Get the corrected offset.
   *
   * @param int $index
   *   The index reported by xml_get_current_byte_index(), which has overflowed
   *   a signed 32-bit integer.
   * @param int $pos
   *   The position in the file at the end of the chunk being parsed... could
   *   possibly run into funky behaviour with boundary conditions, but... They
   *   should be fine.
   *
   * @return int
   *   The corrected offset.
   */
  protected static function correctOffset($index, $pos) {
    $slot = intdiv($pos, 2 ** 31);

    $val = $index +
      ($slot % 2) * (($slot + 1) * 2 ** 31) +
      (($slot + 1) % 2) * ($slot * 2 ** 31);

    return $val;
  }

  /**
   * {@inheritdoc}
   */
  protected function pop() {
    $this->output = parent::pop();
    return $this->output;
  }

}
