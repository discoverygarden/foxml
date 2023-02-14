<?php

namespace Drupal\foxml\Utility\Fedora3;

/**
 * Interface for XML parsing.
 */
interface ParserInterface {

  const TAG = '::undefined:tag::';

  /**
   * XML parser element start callback.
   *
   * @param resource $parser
   *   The active parser.
   * @param string $tag
   *   The tag opening.
   * @param string[] $attributes
   *   The attributes present in the opening tag.
   */
  public function tagOpen($parser, $tag, array $attributes) : void;

  /**
   * XML parser element end callback.
   *
   * @param resource $parser
   *   The active parser.
   * @param string $tag
   *   The tag closing.
   */
  public function tagClose($parser, $tag) : void;

  /**
   * XML parser character data callback.
   *
   * @param resource $parser
   *   The active parser.
   * @param string $chars
   *   The characters received.
   */
  public function characters($parser, $chars) : void;

}
