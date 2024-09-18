<?php

namespace Drupal\foxml\StreamWrapper;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;

/**
 * FOXML-specific stream wrapper method interface.
 */
interface FoxmlInterface extends StreamWrapperInterface {

  /**
   * Build out URI for an object.
   *
   * @param string $id
   *   The ID of the object for which to build out a URI.
   *
   * @return string
   *   The built URI.
   */
  public function getObjectUri(string $id) : string;

  /**
   * Build URI for a datastream.
   *
   * @param string $id
   *   The datastream ID of which to build out a URI.
   * @param string $extension
   *   An extension to use in a filename placeholder.
   *
   * @return string
   *   The built URI.
   */
  public function getDatastreamUri(string $id, string $extension) : string;

}
