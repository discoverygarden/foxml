<?php

namespace Drupal\foxml\Utility\Fedora3;

use Drupal\foxml\Utility\Fedora3\Exceptions\LowLevelDereferenceFailedException;
use Drupal\foxml\Utility\Fedora3\Exceptions\MissingLowLevelStorageAdapterException;

/**
 * Abstract adapter manager/service collector.
 */
abstract class AbstractLowLevelAdapterManager implements LowLevelAdapterInterface {

  /**
   * Collected adapters in buckets based on priority.
   *
   * @var array
   */
  protected $adapters = [];

  /**
   * Sorted array of adapters, or NULL.
   *
   * @var \Drupal\foxml\Utility\Fedora3\LowLevelAdapterInterface[]|null
   */
  protected $sortedAdapters = NULL;

  /**
   * Sorted array of valid adapters, or NULL.
   *
   * @var \Drupal\foxml\Utility\Fedora3\LowLevelAdapterInterface[]|null
   */
  protected $validAdapters = NULL;

  /**
   * Hacky "generics" approach.
   *
   * @param \Drupal\foxml\Utility\Fedora3\LowLevelAdapterInterface $adapter
   *   The adapter to test.
   *
   * @throws \InvalidArgumentException
   *   If the interface does not match.
   */
  protected function matchesInterface(LowLevelAdapterInterface $adapter) : void {
    // No-op.
  }

  /**
   * Service collector callback; add the adapter.
   *
   * @param \Drupal\foxml\Utility\Fedora3\LowLevelAdapterInterface $adapter
   *   The adapter to add.
   * @param int $priority
   *   The priority of the adapter.
   *
   * @return AbstractLowLevelAdapterManager
   *   The current object.
   */
  public function addAdapter(LowLevelAdapterInterface $adapter, $priority = 0) {
    $this->matchesInterface($adapter);
    $this->adapters[$priority][] = $adapter;

    return $this;
  }

  /**
   * Get a sorted array of the adapters.
   *
   * @return \Drupal\foxml\Utility\Fedora3\LowLevelAdapterInterface[]
   *   The sorted array of adapters.
   */
  protected function sortAdapters() : array {
    $sorted = [];

    krsort($this->adapters);
    foreach ($this->adapters as $adapters) {
      $sorted = array_merge($sorted, $adapters);
    }

    return $sorted;
  }

  /**
   * Gets the adapters, sorted and memoized.
   *
   * @return \Drupal\foxml\Utility\Fedora3\LowLevelAdapterInterface[]
   *   The array of adapters.
   */
  protected function sorted() : array {
    $this->sortedAdapters = $this->sortedAdapters ?? $this->sortAdapters();
    return $this->sortedAdapters;
  }

  /**
   * {@inheritdoc}
   */
  public function valid() : bool {
    if ($this->validAdapters === NULL) {
      $this->validAdapters = array_filter($this->sorted(), function (LowLevelAdapterInterface $interface) {
        return $interface->valid();
      });
    }

    return !empty($this->validAdapters);
  }

  /**
   * {@inheritdoc}
   */
  public function dereference($id) : string {
    if (!$this->valid()) {
      throw new MissingLowLevelStorageAdapterException();
    }

    foreach ($this->validAdapters as $adapter) {
      try {
        return $adapter->dereference($id);
      }
      catch (LowLevelDereferenceFailedException $e) {
        // No-op, try the next?... really, don't often expect multiple to be
        // configured... anyway.
      }
    }

    throw new LowLevelDereferenceFailedException($id);
  }

}
