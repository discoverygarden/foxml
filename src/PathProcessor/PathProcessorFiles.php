<?php

namespace Drupal\foxml\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a path processor to rewrite URLs.
 *
 * As the route system does not allow arbitrary amount of parameters convert
 * the file path to a query parameter on the request.
 */
class PathProcessorFiles implements InboundPathProcessorInterface {

  const PREFIX = '/_foxml/';

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    // Quick exit.
    if (strpos($path, static::PREFIX) === 0) {
      // Routes to FileDownloadController::download().
      if (!$request->query->has('file')) {
        $rest = substr($path, strlen(static::PREFIX));
        $request->query->set('file', $rest);
      }

      return '/_foxml';
    }

    return $path;
  }

}
