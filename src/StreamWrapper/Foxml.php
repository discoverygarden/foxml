<?php

namespace Drupal\foxml\StreamWrapper;

use Drupal\Core\File\FileSystem;
use Drupal\Core\StreamWrapper\LocalReadOnlyStream;
use Drupal\Core\Url;

use Drupal\foxml\Utility\Fedora3\DatastreamLowLevelAdapterInterface;
use Drupal\foxml\Utility\Fedora3\ObjectLowLevelAdapterInterface;

/**
 * FOXML stream wrapper.
 */
class Foxml extends LocalReadOnlyStream {

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
    // phpcs:enable
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectoryPath() {
    throw new \Exception('Overides ::getLocalPath(), so this is not necessary.');
  }

  /**
   * {@inheritdoc}
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
      trigger_error('Failed to dereference URI.', E_USER_WARNING);
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
    $path = str_replace('\\', '/', $this
      ->getTarget());
    return Url::fromRoute('foxml.download', [
      'filepath' => $path,
    ], [
      'absolute' => TRUE,
      'path_processing' => FALSE,
    ])
      ->toString();
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
  public function url_stat($uri, $flags) {
    return static::applyMask(parent::url_stat($uri, $flags));
  }

  /**
   * {@inheritDoc}
   */
  public function stream_stat() {
    return static::applyMask(parent::stream_stat());
  } // phpcs:enable Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

}
