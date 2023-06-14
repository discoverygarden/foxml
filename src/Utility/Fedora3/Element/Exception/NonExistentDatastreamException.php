<?php

namespace Drupal\foxml\Utility\Fedora3\Element\Exception;

class NonExistentDatastreamException extends \Exception {

  /**
   * Constructor.
   *
   * @param mixed $PID
   * @param string $datastream_id
   */
  public function __construct($PID, string $datastream_id) {
    parent::__construct("Could not find $PID/$datastream_id");
    $this->pid = $PID;
    $this->dsid = $datastream_id;
  }

}
