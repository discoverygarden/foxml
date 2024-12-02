<?php

namespace Drupal\foxml\StreamWrapper;

use Drupal\Core\File\FileSystem;
use Drupal\Core\StreamWrapper\ReadOnlyStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\Url;

use Drupal\foxml\Utility\Fedora3\DatastreamLowLevelAdapterInterface;
use Drupal\foxml\Utility\Fedora3\ObjectLowLevelAdapterInterface;
use Psr\Log\LoggerInterface;

/**
 * FOXML stream wrapper.
 */
class Foxml extends ReadOnlyStream {

  use NotWritableTrait;

  /**
   * The datastream low-level adapter manager service.
   *
   * @var \Drupal\foxml\Utility\Fedora3\DatastreamLowLevelAdapterInterface
   */
  protected DatastreamLowLevelAdapterInterface $datastreamAdapter;

  /**
   * The object low-level adapter manager service.
   *
   * @var \Drupal\foxml\Utility\Fedora3\ObjectLowLevelAdapterInterface
   */
  protected ObjectLowLevelAdapterInterface $objectAdapter;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected FileSystem $fileSystem;

  /**
   * Logging channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   *
   * Note this cannot take any arguments; PHP's stream wrapper users
   * do not know how to supply them.
   */
  public function __construct() {
    // phpcs:disable DrupalPractice.Objects.GlobalDrupal.GlobalDrupal
    // XXX: DI is not possible in stream wrappers.
    $this->datastreamAdapter = \Drupal::service('foxml.parser.datastream_lowlevel_storage');
    $this->objectAdapter = \Drupal::service('foxml.parser.object_lowlevel_storage');
    $this->fileSystem = \Drupal::service('file_system');
    $this->logger = \Drupal::service('logger.channel.foxml');
    // phpcs:enable
  }

  /**
   * {@inheritDoc}
   */
  protected function getLocalPath($uri = NULL) {
    // XXX: Adapted from LocalReadOnlyStream::getLocalPath().
    if (!isset($uri)) {
      $uri = $this->uri;
    }
    $target = $this->getTarget($uri);
    $exploded = explode('/', $target, 2);
    if (count($exploded) === 1) {
      // XXX: `foxml://object` and `foxml://datastream` on their own do not
      // really point anywhere.
      return FALSE;
    }
    [$subtype, $target_actual] = $exploded;
    assert(in_array($subtype, ['object', 'datastream']), 'Valid URI.');

    try {
      $path = $this->{"{$subtype}Adapter"}->dereference($target_actual);
      assert(is_string($path), 'Dereferenced path.');
    }
    catch (\Exception $e) {
      $this->logger->warning('Failed to dereference URI "{uri}". Exception info: {message}, {trace}', [
        'uri' => $uri,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);
      return FALSE;
    }

    // In PHPUnit tests, the base path for local streams may be a virtual
    // filesystem stream wrapper URI, in which case this local stream acts like
    // a proxy. realpath() is not supported by vfsStream, because a virtual
    // file system does not have a real filepath.
    if (strpos($path, 'vfs://') === 0) {
      return $path;
    }
    $realpath = $this->fileSystem->realpath($path);
    if (!$realpath) {

      // This file does not yet exist, or $path references something remote.
      $realpath = $this->fileSystem->dirname($path) . '/' .
        $this->fileSystem->basename($path);
    }

    return $realpath;
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    // XXX: Copypasta from
    // \Drupal\Core\StreamWrapper\PrivateStream::getExternalUrl().
    $path = str_replace('\\', '/', $this->getTarget());
    return Url::fromRoute('foxml.download', [
      'filepath' => $path,
    ], [
      'absolute' => TRUE,
      'path_processing' => FALSE,
    ])
      ->toString();
  }

  /**
   * Returns the local target of the resource within the stream.
   *
   * This function should be used in place of calls to realpath() or similar
   * functions when attempting to determine the location of a file. While
   * functions like realpath() may return the location of a read-only file, this
   * method may return a URI or path suitable for writing that is completely
   * separate from the URI used for reading.
   *
   * @param null|string $uri
   *   Optional URI.
   *
   * @return string
   *   Returns a string representing a location suitable for writing of a file.
   */
  protected function getTarget(?string $uri = NULL) : string {
    if (!isset($uri)) {
      $uri = $this->uri;
    }

    [, $target] = explode('://', $uri, 2);

    // Remove erroneous leading or trailing, forward-slashes and backslashes.
    return trim($target, '\/');
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'FOXML';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return 'FOXML object/datastream storage dereferencing.';
  }

  /**
   * {@inheritDoc}
   * phpcs:disable Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
   */
  public function url_stat($path, $flags) {
    return ($flags & STREAM_URL_STAT_QUIET) ?
      @static::applyMask(stat($this->getLocalPath($path))) :
      static::applyMask(stat($this->getLocalPath($path)));
  }

  /**
   * {@inheritDoc}
   */
  public function stream_stat() {
    return static::applyMask(fstat($this->handle));
  }

  /**
   * {@inheritDoc}
   */
  public function dir_closedir() {
    trigger_error(__FUNCTION__ . '() not supported for foxml stream wrapper.', E_USER_WARNING);
    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function dir_opendir($path, $options) {
    trigger_error(__FUNCTION__ . '() not supported for foxml stream wrapper.', E_USER_WARNING);
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function dir_readdir() {
    trigger_error(__FUNCTION__ . '() not supported for foxml stream wrapper.', E_USER_WARNING);
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function dir_rewinddir() {
    trigger_error(__FUNCTION__ . '() not supported for foxml stream wrapper.', E_USER_WARNING);
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function stream_cast($cast_as) {
    trigger_error(__FUNCTION__ . '() not supported for foxml stream wrapper.', E_USER_WARNING);
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function stream_close() {
    $result = fclose($this->handle);
    unset($this->handle);
    return $result;
  }

  /**
   * {@inheritDoc}
   */
  public function stream_eof() {
    return feof($this->handle);
  }

  /**
   * {@inheritDoc}
   */
  public function stream_read($count) {
    return fread($this->handle, $count);
  }

  /**
   * {@inheritDoc}
   */
  public function stream_seek($offset, $whence = SEEK_SET) {
    return fseek($this->handle, $offset, $whence);
  }

  /**
   * {@inheritDoc}
   */
  public function stream_set_option($option, $arg1, $arg2) {
    return stream_context_set_option($this->handle, $option, $arg1, $arg2);
  }

  /**
   * {@inheritDoc}
   */
  public function stream_tell() {
    return ftell($this->handle);
  } // phpcs:enable Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

  /**
   * {@inheritDoc}
   */
  public static function getType() {
    return StreamWrapperInterface::HIDDEN;
  }

  /**
   * {@inheritDoc}
   */
  public function realpath() {
    trigger_error(__FUNCTION__ . '() not supported for foxml stream wrapper.', E_USER_WARNING);
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function dirname($uri = NULL) {
    trigger_error(__FUNCTION__ . '() not supported for foxml stream wrapper.', E_USER_WARNING);
    return FALSE;
  }

}
