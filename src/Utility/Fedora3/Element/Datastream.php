<?php

namespace Drupal\foxml\Utility\Fedora3\Element;

use Drupal\foxml\Utility\Fedora3\AbstractParser;
use Drupal\foxml\Utility\Fedora3\ParserInterface;

/**
 * Element handler for foxml:datastream.
 */
class Datastream extends AbstractParser implements \ArrayAccess {
  const TAG = 'foxml:datastream';
  const MAP = [
    DatastreamVersion::TAG => DatastreamVersion::class,
  ];

  /**
   * The array of versions present for the given datastream.
   *
   * @var \Drupal\foxml\Utility\Fedora3\Element\DatastreamVersion[]
   */
  protected array $versions = [];

  /**
   * {@inheritdoc}
   */
  public function __sleep() : array {
    return array_merge(parent::__sleep(), [
      'versions',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function pop() : ParserInterface {
    $old = parent::pop();

    $this[$old->id()] = $old;

    return $old;
  }

  /**
   * Accessor for the ID of this datastream.
   *
   * @return string
   *   The ID of this datastream.
   */
  public function id() : string {
    return $this->ID;
  }

  /**
   * Accessor for the latest datastream version represented.
   *
   * @return \Drupal\foxml\Utility\Fedora3\Element\DatastreamVersion
   *   The latest datastream version.
   */
  public function latest() : DatastreamVersion {
    return end($this->versions);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($offset) : bool {
    return isset($this->versions[$offset]);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet($offset) : DatastreamVersion {
    return $this->versions[$offset];
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($offset, $value) : void {
    if (!isset($this[$offset])) {
      $this->versions[$offset] = $value;
    }
    else {
      throw new Exception("Refusing to replace {$offset}.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($offset) : void {
    throw new Exception('Not implemented.');
  }

  /**
   * Helper; grab the URI for the latest datastream version.
   *
   * @return string
   *   The URI of the latest datastream version.
   */
  public function getUri() : string {
    return $this->latest()->content()->getUri();
  }

}
