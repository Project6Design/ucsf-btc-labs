<?php

namespace Drupal\photoswipe;

use Drupal\file\FileInterface;

/**
 * Reads SVG files and strips untrusted markup from them.
 */
interface SvgSanitizerInterface {

  /**
   * Returns the sanitized contents of an SVG file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The SVG file.
   * @param bool $forInlineOutput
   *   (optional) TRUE if the markup is going to be embedded into an HTML
   *   document, rather than served as a standalone SVG file. This removes the
   *   XML declaration.
   *
   * @return string|null
   *   The sanitized markup, or NULL if the file could not be read or
   *   sanitized.
   */
  public function sanitizeFile(FileInterface $file, bool $forInlineOutput = FALSE): ?string;

}
