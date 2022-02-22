<?php

namespace Drupal\foxml\Utility\Fedora3;

use Drupal\foxml\Utility\Fedora3\Exceptions\NotImplementedException;

use Drupal\Core\Site\Settings;

/**
 * Archival object adapter.
 */
class ArchivalObjectLowLevelAdapter implements ObjectLowLevelAdapterInterface {

  /**
   * The directory over which to iterate.
   *
   * @var string
   */
  protected $basePath;

  /**
   * Optional regex pattern against which to match files.
   *
   * @var string
   */
  protected $pattern;

  /**
   * Constructor.
   */
  public function __construct(Settings $settings) {
    $this->basePath = $settings->get('foxml_archival_object_basepath', 'private://exports');
    $this->pattern = $settings->get('foxml_archival_object_file_pattern', NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() : \Traversable {
    // Adapted from migrate_directory.
    // @see https://github.com/discoverygarden/migrate_directory/blob/87d47cd69f037f5d239af0c8124c3ed433146413/src/Plugin/migrate/source/MigrateDirectory.php#L36-L69
    //
    // Always get UNIX paths, skipping . and .., key as filename, and follow
    // links.
    $flags = FilesystemIterator::UNIX_PATHS |
             FilesystemIterator::SKIP_DOTS |
             FilesystemIterator::KEY_AS_FILENAME |
             FilesystemIterator::FOLLOW_SYMLINKS;

    // Recurse through the directory.
    $files = new \RecursiveDirectoryIterator($this->basePath, $flags);

    // A filter could be added here if necessary.
    if ($this->pattern) {
      $filter = new \RecursiveCallbackFilterIterator(
        $files,
        [$this, 'filterCallback']
      );
    }
    else {
      $filter = $files;
    }

    // Get an iterator of our iterator...
    return new \RecursiveIteratorIterator($filter);
  }

  /**
   * {@inheritdoc}
   */
  public function getIteratorType() : int {
    return ObjectLowLevelAdapterInterface::ITERATOR_SPLFILEINFO;
  }

  /**
   * Helper; filter according to preg_match using pattern.
   *
   * Adapted from migrate_directory.
   *
   * @param \SplFileInfo $current
   *   The item being considered.
   * @param mixed $key
   *   The key for the item being considered.
   * @param \Iterator $iterator
   *   The iterator in which this filter callback is being used.
   *
   * @return bool
   *   TRUE if the given item should be considered; otherwise, FALSE.
   *
   * @see https://github.com/discoverygarden/migrate_directory/blob/87d47cd69f037f5d239af0c8124c3ed433146413/src/Plugin/migrate/source/MigrateDirectory.php#L49-L62
   */
  protected function filterCallback(\SplFileInfo $current, $key, \Iterator $iterator) : bool {
    // Get the current item's name.
    /** @var \SplFileInfo $current */
    $filename = $current->getFilename();

    if ($current->isDir()) {
      // Always descend into directories.
      return TRUE;
    }

    // Match the filename against the pattern.
    return preg_match($this->pattern, $filename) === 1;
  }

  /**
   * {@inheritdoc}
   */
  public function dereference($id) : string {
    throw new NotImplementedException('Would need some kind of templatey business to deal with however filenames were exported?');
  }

  /**
   * {@inheritdoc}
   */
  public function valid() : bool {
    return is_dir($this->basePath);
  }

}