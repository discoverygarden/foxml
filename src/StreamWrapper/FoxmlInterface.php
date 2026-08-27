<?php

namespace Drupal\foxml\StreamWrapper;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;

/**
 * Define interface for our stream wrapper.
 */
interface FoxmlInterface extends StreamWrapperInterface {

  /**
   * Attempt to get URI of a wrapped asset.
   *
   * @return string|false
   *   The wrapped URI if one was found; otherwise, FALSE.
   */
  public function unwrap() : string|false;

}
