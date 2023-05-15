<?php

namespace Drupal\foxml\Utility\Fedora3;

/**
 * Base low-level adapter interface.
 */
interface LowLevelAdapterInterface {

  /**
   * Dereference an ID from the given storage.
   *
   * @param string $id
   *   The ID to dereference.
   *
   * @return string
   *   A URI/path to the given resource.
   *
   * @throws \Drupal\foxml\Utility\Fedora3\Exceptions\LowLevelDereferenceFailedException
   *   Should be thrown if we fail to dereference the given ID.
   */
  public function dereference($id) : string;

  /**
   * Check if the given adapter is ready to operate.
   *
   * @return bool
   *   TRUE if the given adapter is configured and ready to ::dereference()
   *   things; otherwise, FALSE.
   */
  public function valid() : bool;

}
