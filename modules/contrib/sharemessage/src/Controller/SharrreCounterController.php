<?php

namespace Drupal\sharemessage\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Route controller class for the Sharrre - Google Plus and Stumbleupon counter.
 */
class SharrreCounterController extends ControllerBase {

  /**
   * Guzzle HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * Get the counter for Google Plus and Stumbleupon.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request from which we get the counter.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the counter.
   */
  public function getCounter(Request $request) {
    // Sharrre by Julien Hany.
    $json = ['url' => $request->get('url'), 'count' => 0];
    $url = urlencode($json['url']);
    $type = $request->get('type');

    if (filter_var($json['url'], FILTER_VALIDATE_URL)) {
      if ($type == 'googlePlus') {
        $json['count'] = $this->getCounterGooglePlus($url);
      }
      elseif ($type == 'stumbleupon') {
        $json['count'] = $this->getCounterStumbleupon($url);
      }
    }
    return new JsonResponse($json);
  }

  /**
   * Get the counter for Google Plus.
   *
   * Source http://www.helmutgranda.com/2011/11/01/get-a-url-google-count-via-php/
   *
   * @param string $url
   *   Requested URL.
   *
   * @return int
   *   Returns the counter for Google Plus.
   */
  protected function getCounterGooglePlus($url) {
    $contents = $this->parse('https://plusone.google.com/u/0/_/+1/fastbutton?url=' . $url . '&count=true');

    preg_match('/window\.__SSR = {c: ([\d]+)/', $contents, $matches);

    // If the counter is set, remove the extra strings around it and save it in
    // the JSON array.
    if (isset($matches[0])) {
      return (int) str_replace('window.__SSR = {c: ', '', $matches[0]);
    }
  }

  /**
   * Get the counter for Stumbleupon.
   *
   * @param string $url
   *   Requested URL.
   *
   * @return int
   *   Returns the counter for Stumbleupon.
   */
  protected function getCounterStumbleupon($url) {
    $content = $this->parse("http://www.stumbleupon.com/services/1.01/badge.getinfo?url=$url");

    $result = json_decode($content);
    if (isset($result->result->views)) {
      return $result->result->views;
    }
  }

  /**
   * Parse the counter information.
   *
   * @param string $enc_url
   *   URL to parse.
   *
   * @return mixed
   *   Returns the content.
   */
  public function parse($enc_url) {
    try {
      $response = \Drupal::httpClient()->request('GET', $enc_url);
    }
    catch (BadResponseException $e) {
      $error = $e->getResponse()->json();
      watchdog_exception('sharrre', $e, $error['error']['message']);
      return;
    }

    // Process the JSON result into array.
    return $response->getBody()->getContents();
  }

}
