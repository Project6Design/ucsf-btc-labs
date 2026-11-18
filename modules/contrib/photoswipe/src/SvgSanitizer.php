<?php

namespace Drupal\photoswipe;

use Drupal\file\FileInterface;
use enshrined\svgSanitize\Sanitizer;

/**
 * Reads SVG files and strips untrusted markup from them.
 *
 * Note: Only the svg submodules need this, and the enshrined/svg-sanitize
 * library they require. It is not used by the core Photoswipe formatter.
 */
class SvgSanitizer implements SvgSanitizerInterface {

  /**
   * {@inheritdoc}
   */
  public function sanitizeFile(FileInterface $file, bool $forInlineOutput = FALSE): ?string {
    // Ignore "E_WARNING" through "@" suppression operator and return NULL
    // instead:
    $contents = @file_get_contents($file->getFileUri());
    if ($contents === FALSE) {
      return NULL;
    }

    $sanitizer = new Sanitizer();
    // An XML declaration has no place inside an HTML document.
    $sanitizer->removeXMLTag($forInlineOutput);
    $sanitized = $sanitizer->sanitize($contents);

    return $sanitized === FALSE ? NULL : $sanitized;
  }

}
