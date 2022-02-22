<?php

namespace Drupal\foxml\Utility\Fedora3;

/**
 * Datastream low-level adapter service collector.
 */
class DatastreamLowLevelAdapterManager extends AbstractLowLevelAdapterManager implements DatastreamLowLevelAdapterInterface {

  /**
   * {@inheritdoc}
   */
  protected function matchesInterface(LowLevelAdapterInterface $adapter) : void {
    if (!($adapter instanceof DatastreamLowLevelAdapterInterface)) {
      throw new \InvalidArgumentException('Adapter is not instance of DatastreamLowLevelAdapterInterface.');
    }

    parent::matchesInterface($adapter);
  }

}
