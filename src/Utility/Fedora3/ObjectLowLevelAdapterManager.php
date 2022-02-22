<?php

namespace Drupal\foxml\Utility\Fedora3;

/**
 * Object low-level adapter service collector.
 */
class ObjectLowLevelAdapterManager extends AbstractLowLevelAdapterManager implements ObjectLowLevelAdapterInterface {

  /**
   * {@inheritdoc}
   */
  protected function matchesInterface(LowLevelAdapterInterface $adapter) : void {
    if (!($adapter instanceof ObjectLowLevelAdapterInterface)) {
      throw new \InvalidArgumentException('Adapter is not instance of ObjectLowLevelAdapterInterface.');
    }

    parent::matchesInterface($adapter);
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() : \Traversable {
    $this->valid();

    return reset($this->validAdapters);
  }

  /**
   * {@inheritdoc}
   */
  public function getIteratorType() : int {
    return $this->getIterator()->getIteratorType();
  }

}
