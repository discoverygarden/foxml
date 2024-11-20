<?php

namespace Drupal\foxml\Utility\Fedora3;

use Drupal\foxml\Utility\Fedora3\Exceptions\NotImplementedException;
use Psr\Log\LoggerInterface;

/**
 * Object low-level adapter service collector.
 */
class ObjectLowLevelAdapterManager extends AbstractLowLevelAdapterManager implements ObjectLowLevelAdapterInterface {

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   */
  public function __construct(
    LoggerInterface $logger,
  ) {
    $this->logger = $logger;
  }

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

    if ($first_valid_adapter = reset($this->validAdapters)) {
      return $first_valid_adapter;
    }
    else {
      $this->logger->notice('No valid low-level FOXML object adapter found; configure as necessary, if relevant.');
      return new class() implements ObjectLowLevelAdapterInterface {

        /**
         * {@inheritDoc}
         */
        public function dereference($id) : string {
          throw new NotImplementedException('Attempting to dereference from an invalid adapter.');
        }

        /**
         * {@inheritDoc}
         */
        public function getIterator() : \Traversable {
          return new \EmptyIterator();
        }

        /**
         * {@inheritDoc}
         */
        public function getIteratorType() : int {
          return ObjectLowLevelAdapterInterface::ITERATOR_PID;
        }

        /**
         * {@inheritDoc}
         */
        public function valid() : bool {
          return FALSE;
        }

      };
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIteratorType() : int {
    return $this->getIterator()->getIteratorType();
  }

}
