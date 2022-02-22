<?php

namespace Drupal\foxml\Plugin\migrate\source;

use Drupal\foxml\Utility\Fedora3\ObjectLowLevelAdapterInterface;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * FOXML migration source plugin.
 *
 * @MigrateSource(
 *   id = "foxml",
 *   source_module = "foxml",
 * )
 */
class Foxml extends SourcePluginBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The object store from which we are to operate.
   *
   * @var \Drupal\foxml\Utility\Fedora3\ObjectLowLevelAdapterInterface
   */
  protected $objectStorage;

  /**
   * Constructor.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration,
    ObjectLowLevelAdapterInterface $object_storage
  ) {
    $this->objectStorage = $object_storage;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration = NULL
  ) {
    // Allow a specific storage to be targeted.
    $object_service_name = $configuration['object_service_name'] ?? 'foxml.parser.object_lowlevel_storage';

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get($object_service_name)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function rewind() {
    // XXX: "rewind()" by recreating the underlying iterator, since we make
    // use of the generator business.
    unset($this->iterator);
    $this->next();
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    return $this->generateFileinfoArray();
  }

  /**
   * Helper; generate PIDs for our object source.
   *
   * @return \Traversable
   *   The PID iterator.
   *
   * @throws \InvalidArgumentException when the
   *   ::objectStorage->getIteratorType() is not handled.
   */
  protected function generatePids() : \Traversable {
    if ($this->objectStorage->getIteratorType() === ObjectLowLevelAdapterInterface::ITERATOR_PID) {
      return $this->objectStorage->getIterator();
    }

    throw new \InvalidArgumentException('Unrecognized ::getIteratorType() value.');
  }

  /**
   * Helper; generate filesystem paths for our object source.
   *
   * @return \Traversable
   *   Either:
   *   - returns the iterator directly, if it's of the correct type; or,
   *   - uses the generated PIDs and dereferences them as they're called for.
   */
  protected function generatePaths() : \Traversable {
    if ($this->objectStorage->getIteratorType() === ObjectLowLevelAdapterInterface::ITERATOR_PATH) {
      return $this->objectStorage->getIterator();
    }

    foreach ($this->generatePids() as $key => $pid) {
      yield $key => $this->objectStorage->dereference($pid);
    }
  }

  /**
   * Helper; generate \SplFileInfo instances for our object source.
   *
   * Either:
   * - returns the iterator directly, if it's of the correct type; or,
   * - uses the generated paths to instantiate \SplFileInfo instances as they're
   *   called for.
   */
  protected function generateFileinfo() : \Traversable {
    if ($this->objectStorage->getIteratorType() === ObjectLowLevelAdapterInterface::ITERATOR_SPLFILEINFO) {
      return $this->objectStorage->getIterator();
    }

    foreach ($this->generatePaths() as $key => $path) {
      yield $key => (new \SplFileInfo($path));
    }
  }

  /**
   * Helper; map an \SplFileInfo instance to an array for persistence.
   *
   * @param \SplFileInfo $fileinfo
   *   The instance to be mapped.
   *
   * @return array
   *   An associative array containing:
   *   - path: The path the file.
   *   - absolute_path: The absolute path to the file.
   *   - filename: The filename.
   *   - basename: The file's basename.
   *   - extension: The file's extension.
   *   - uri: A URI to the file.
   */
  protected static function mapSplfileinfoToArray(\SplFileInfo $fileinfo) : array {
    return [
      'path' => $fileinfo->getPathname(),
      'absolute_path' => $fileinfo->getRealPath(),
      'filename' => $fileinfo->getFilename(),
      'basename' => $fileinfo->getBasename(),
      'extension' => $fileinfo->getExtension(),
      'uri' => "file://{$fileinfo->getRealPath()}",
    ];
  }

  /**
   * Helper; generate file info for iterator.
   */
  protected function generateFileinfoArray() : \Traversable {
    foreach ($this->prefilter($this->generateFileinfo()) as $key => $info) {
      yield $key => static::mapSplfileinfoToArray($info);
    }
  }

  /**
   * Helper; avoid returning things that might be deleted.
   *
   * @param \Traversable $iterator
   *   The iterator returning \SplFileInfo instances to be filtered.
   *
   * @return \Traversable
   *   An iterator of read-only files.
   */
  protected function prefilter(\Traversable $iterator) : \Traversable {
    return new \CallbackFilterIterator($iterator, function ($file, $key, $iterator) {
      $result = $file->isFile() && !$file->isDir() && $file->isReadable() && !$file->isWritable() &&
        !$file->getPathInfo()->isWritable();
      return $result;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = [];

    $ids['path']['type'] = 'string';

    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'path' => $this->t('The file path'),
      'relative_path' => $this->t('The file path relative to the directory given source plugin'),
      'absolute_path' => $this->t('The absolute path to the file, resolving links'),
      'filename' => $this->t('The filename'),
      'basename' => $this->t('The basename of the file'),
      'extension' => $this->t('The extension of the file, if any'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return (string) $this->configuration['path'];
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    $vars = parent::__sleep();

    $to_suppress = [
      // XXX: Avoid serializing some things can't be natively serialized.
      'iterator',
    ];
    foreach ($to_suppress as $value) {
      $key = array_search($value, $vars);
      if ($key !== FALSE) {
        unset($vars[$key]);
      }
    }

    return $vars;
  }

}
