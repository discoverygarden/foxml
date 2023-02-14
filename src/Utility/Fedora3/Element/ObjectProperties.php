<?php

namespace Drupal\foxml\Utility\Fedora3\Element;

use Drupal\foxml\Utility\Fedora3\AbstractParser;

/**
 * Element handler for foxml:objectProperties.
 */
class ObjectProperties extends AbstractParser implements \ArrayAccess {

  const TAG = 'foxml:objectProperties';
  const MAP = [
    ObjectProperty::TAG => ObjectProperty::class,
    ExtObjectProperty::TAG => ExtObjectProperty::class,
  ];

  /**
   * An associative array mapping properties to instances representing them.
   *
   * @var \Drupal\foxml\Utility\Fedora3\Element\ObjectProperty[]
   */
  protected $properties = [];

  /**
   * {@inheritdoc}
   */
  protected function pop() {
    $old = parent::pop();

    $this[$old->id()] = $old;

    return $old;
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    return array_merge(parent::__sleep(), [
      'properties',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($offset) {
    return isset($this->properties[$offset]);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet($offset) {
    return $this->properties[$offset];
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($offset, $value) {
    if (!isset($this[$offset])) {
      $this->properties[$offset] = $value;
    }
    else {
      throw new \Exception("Refusing to replace {$offset}.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($offset) {
    throw new \Exception('Not implemented.');
  }

}
