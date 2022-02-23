<?php

namespace Drupal\foxml\StreamWrapper;

use Drupal\foxml\Utility\Fedora3\DatastreamLowLevelAdapterInterface;
use Drupal\foxml\Utility\Fedora3\ObjectLowLevelAdapterInterface;

use Drupal\Core\File\FileSystem;
use Drupal\Core\StreamWrapper\LocalReadOnlyStream;
use Drupal\Core\Url;

class Foxml extends LocalReadOnlyStream {

  protected DatastreamLowLevelAdapterInterface $datastreamAdapter;
  protected ObjectLowLevelAdapterInterface $objectAdapter;
  protected FileSystem $fileSystem;

  /**
   * Constructor.
   *
   * Note this cannot take any arguments; PHP's stream wrapper users
   * do not know how to supply them.
   */
  public function __construct() {
    $this->datastreamAdapter = \Drupal::service('foxml.parser.datastream_lowlevel_storage');
    $this->objectAdapter = \Drupal::service('foxml.parser.object_lowlevel_storage');
    $this->fileSystem = \Drupal::service('file_system');
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
    [$subtype, $target_actual] = explode('/', $target, 2);
    assert(in_array($subtype, ['object', 'datastream']), 'Valid URI.');

    try {
      $path = $this->{"{$subtype}Adapter"}->dereference($target_actual);
    }
    catch (\Exception $e) {
      var_dump('asdfasdf');
      return FALSE;
    }

    // In PHPUnit tests, the base path for local streams may be a virtual
    // filesystem stream wrapper URI, in which case this local stream acts like
    // a proxy. realpath() is not supported by vfsStream, because a virtual
    // file system does not have a real filepath.
    if (strpos($path, 'vfs://') === 0) {
      return $path;
    }
    $realpath = realpath($path);
    if (!$realpath) {

      // This file does not yet exist.
      $realpath = realpath(dirname($path)) . '/' .
        $this->fileSystem->basename($path);
    }

    return $realpath;
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    // XXX: Copypasta from \Drupal\Core\StreamWrapper\PrivateStream::getExternalUrl().
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
    return 'FOXXML object/datastream storage dereferencing.';
  }

}
