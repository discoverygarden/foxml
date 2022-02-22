<?php

namespace Drupal\foxml\Utility\Fedora3\Element;

use Drupal\foxml\Utility\Fedora3\AbstractParser;

/**
 * Element handler for foxml:contentLocation.
 */
class ContentLocation extends AbstractParser {

  use LeafTrait;
  use EmptyTrait;

  const TAG = 'foxml:contentLocation';

  /**
   * The URI to the content.
   *
   * @var string
   */
  protected $uri = NULL;

  /**
   * {@inheritdoc}
   */
  public function close() {
    // XXX: Memoize the URI, as later closing will lose access to the parser.
    $this->getUri();

    parent::close();
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    return array_merge(parent::__sleep(), [
      'uri',
    ]);
  }

  /**
   * Helper to fetch the URI.
   *
   * @return string
   *   The URI if it's described as a URL.
   *
   * @throws \Exception
   *   If the location refers to an internal URI.
   */
  public function getUri() {
    if ($this->uri === NULL) {
      if ($this->TYPE === 'URL') {
        $this->uri = $this->REF;
      }
      elseif ($this->TYPE === 'INTERNAL_ID') {
        $this->uri = $this->getFoxmlParser()->getDatastreamLowLevelAdapter()->dereference($this->REF);
      }
      else {
        throw new \Exception('Unhandled type.');
      }
    }

    return $this->uri;
  }

}
