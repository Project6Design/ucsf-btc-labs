<?php

/**
 * @file
 * Hooks provided by url_embed module.
 */

/**
 * Alter url or options passed to the embed request before sending it.
 *
 * @param string $url
 *   The URL.
 * @param array $config
 *   Options passed to the adapter.
 */
function hook_url_embed_options_alter(&$url, &$config) {
  $parsedUrl = parse_url($url);

  if (($parsedUrl['host'] ?? '') == 'twitter.com') {
    $config = array_merge($config, ['oembed' => ['parameters' => ['maxheight' => 600]]]);
  }
}
