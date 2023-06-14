<?php

namespace Drupal\foxml\Utility\Fedora3\Element\Exception;

/**
 * Exception for flow control; thrown if we require an absent datastream.
 */
class NonExistentDatastreamException extends \Exception {
  /**
   * The PID of the object missing the datastream.
   *
   * @var mixed
   */
  protected $pid;

  /**
   * The ID of the missing datastream.
   *
   * @var string
   */
  protected string $datastreamId;

  /**
   * Constructor.
   *
   * @param mixed $pid
   *   The PID that's missing the datastream.
   * @param string $datastream_id
   *   The datastream that's missing.
   */
  public function __construct($pid, string $datastream_id) {
    parent::__construct("Could not find $pid/$datastream_id");
    $this->pid = $pid;
    $this->datastreamId = $datastream_id;
  }

}
