<?php

namespace Drupal\foxml;

use Drupal\foxml\StreamWrapper\FoxmlInterface;
use Drupal\system\FileDownloadController as UpstreamFileDownloadController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Extended file download controller.
 */
class FileDownloadController extends UpstreamFileDownloadController {

  /**
   * {@inheritDoc}
   */
  public function download(Request $request, $scheme = 'private') {
    // Parent handles access control on the `foxml://` URI.
    $response = parent::download($request, $scheme);

    if (!$response instanceof BinaryFileResponse) {
      return $response;
    }

    $file = $response->getFile();
    $path = $file->getPathname();
    if (!($wrapper = $this->streamWrapperManager->getViaUri($path))) {
      return $response;
    }

    assert($wrapper instanceof FoxmlInterface);
    if ($unwrapped = $wrapper->unwrap()) {
      $response->setFile($unwrapped);
    }
    return $response;
  }

}
