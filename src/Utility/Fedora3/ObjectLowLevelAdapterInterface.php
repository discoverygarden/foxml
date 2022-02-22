<?php

namespace Drupal\foxml\Utility\Fedora3;

/**
 * Object low-level adapter interface.
 */
interface ObjectLowLevelAdapterInterface extends LowLevelAdapterInterface, \IteratorAggregate {

  // Typification constances.
  const ITERATOR_PID = 0;
  const ITERATOR_PATH = 1;
  const ITERATOR_SPLFILEINFO = 2;

  /**
   * Get iterator for all object IDs.
   *
   * @return \Traversable
   *   The iterator.
   *
   * @throws \Drupal\foxml\Utility\Fedora3\NotImplementedException
   *   If the given low-level storage has not implemented the capability.
   */
  public function getIterator() : \Traversable;

  /**
   * Get some info about the iterator; what the iterator returns.
   *
   * @return int
   *   The value of one of the ITERATOR_* constants, indicating that the
   *   the iterator returns...:
   *   - ITERATOR_PID: ... PIDs (needing to be dereferenced)
   *   - ITERATOR_PATH: ... paths to files (to be thrown at SplInfo and
   *     parsed); or,
   *   - ITERATOR_SPLFILEINFO: ... \SplFileInfo objects to be mapped and used.
   */
  public function getIteratorType() : int;

}
