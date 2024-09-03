<?php

namespace Drupal\foxml\StreamWrapper;

/**
 * Helper trait to mask writability from file modes.
 */
trait NotWritableTrait {

  /**
   * Apply mask to suppress writability being reported.
   *
   * @param array|false $result
   *   The stat() result in which to mask the mode.
   *
   * @return array|false
   *   The masked stat() result.
   */
  protected static function applyMask(array|false $result) : array|false {
    if (!$result) {
      return FALSE;
    }

    $result[2] = ($result['mode'] &= !0o222);

    return $result;
  }

}
