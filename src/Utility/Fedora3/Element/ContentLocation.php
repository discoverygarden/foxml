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
        /** @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager */
        $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
        /** @var \Drupal\foxml\StreamWrapper\FoxmlInterface $foxml_wrapper */
        $foxml_wrapper = $stream_wrapper_manager->getViaScheme('foxml');

        // XXX: No MIME-type info available here at the moment. Would need to
        // either pass it in, or have some means to access it from whatever
        // wrapping element (the datastream version?).
        // @todo Figure some way to get a better extension than ".bin".
        $this->uri = $foxml_wrapper->getDatastreamUri($this->REF, 'bin');
      }
      else {
        throw new \Exception('Unhandled type.');
      }
    }

    return $this->uri;
  }

}
