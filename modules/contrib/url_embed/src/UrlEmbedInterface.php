<?php

/**
 * @file
 * Contains Drupal\url_embed\UrlEmbedInterface.
 */

namespace Drupal\url_embed;

use Embed\Extractor;

/**
 * A service class for handling URL embeds.
 *
 * @todo Add more documentation.
 */
interface UrlEmbedInterface {

  public function getConfig();

  public function setConfig(array $config);

  /**
   * Returns the Embed Extractor for the given URL.
   *
   * @param string $url
   *   The URL.
   * @param array $config
   *   (optional) Options passed to the adapter. If not provided the default
   *   options on the service will be used.
   *
   * @return \Embed\Extractor
   *
   * @throws \InvalidArgumentException
   *   If the URL or config is not valid.
   */
  public function getEmbed(string $url, array $config = []): Extractor;

}
