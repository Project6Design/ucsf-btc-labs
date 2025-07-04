<?php

namespace Drupal\url_embed\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;

/**
 * Provides a filter to display embedded entities based on data attributes.
 *
 * @Filter(
 *   id = "url_embed_convert_links",
 *   title = @Translation("Convert URLs to URL embeds"),
 *   description = @Translation("Convert plain URLs to embed elements that can be rendered with the <em>Display embedded URLs</em> filter."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   settings = {
 *     "url_prefix" = "",
 *   },
 * )
 */
#[Filter(
  id: "url_embed_convert_links",
  title: new TranslatableMarkup("Convert URLs to URL embeds"),
  type: FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
  settings: [
    "url_prefix" => "",
  ],
)]
class ConvertUrlToEmbedFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['url_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL prefix'),
      '#default_value' => $this->settings['url_prefix'],
      '#description' => $this->t('Optional prefix that will be used to indicate which URLs that apply. All URLs that are supported will be converted if empty. Example: EMBED-https://www.youtube.com/watch?v=I95hSyocMlg'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult(static::convertUrls($text, $this->settings['url_prefix']));
  }

  /**
   * Replaces appearances of supported URLs with <drupal-url> embed elements.
   *
   * Logic of this function is copied from _filter_url() and slightly adopted
   * for our use case. _filter_url() is unfortunately not general enough to
   * re-use it.
   *
   * @param string $text
   *   Text to be processed.
   * @param string $url_prefix
   *   (Optional) Prefix that should be used to manually choose which URLs
   *   should be converted.
   *
   * @return string
   *   Processed text.
   */
  public static function convertUrls($text, $url_prefix = '') {
    // Tags to skip and not recurse into.
    $ignore_tags = 'a|script|style|code|pre';

    // Create an array which contains the regexps for each type of link.
    // The key to the regexp is the name of a function that is used as
    // callback function to process matches of the regexp. The callback function
    // is to return the replacement for the match. The array is used and
    // matching/replacement done below inside some loops.
    $tasks = [];

    // Prepare protocols pattern for absolute URLs.
    // \Drupal\Component\Utility\UrlHelper::stripDangerousProtocols() will
    // replace any bad protocols with HTTP, so we need to support the identical
    // list. While '//' is technically optional for MAILTO only, we cannot
    // cleanly differ between protocols here without hard-coding MAILTO, so '//'
    // is optional for all protocols.
    // @see \Drupal\Component\Utility\UrlHelper::stripDangerousProtocols()
    $protocols = \Drupal::getContainer()->getParameter('filter_protocols');
    $protocols = implode(':(?://)?|', $protocols) . ':(?://)?';

    $valid_url_path_characters = "[\p{L}\p{M}\p{N}!\*\';:=\+,\.\$\/%#\[\]\-_~@&]";

    // Allow URL paths to contain balanced parens
    // 1. Used in Wikipedia URLs like /Primer_(film)
    // 2. Used in IIS sessions like /S(dfd346)/.
    $valid_url_balanced_parens = '\(' . $valid_url_path_characters . '+\)';

    // Valid end-of-path characters (so /foo. does not gobble the period).
    // 1. Allow =&# for empty URL parameters and other URL-join artifacts.
    $valid_url_ending_characters = '[\p{L}\p{M}\p{N}:_+~#=/]|(?:' . $valid_url_balanced_parens . ')';

    $valid_url_query_chars = '[a-zA-Z0-9!?\*\'@\(\);:&=\+\$\/%#\[\]\-_\.,~|]';
    $valid_url_query_ending_chars = '[a-zA-Z0-9_&=#\/]';

    // Full path
    // and allow @ in a url, but only in the middle. Catch things like
    // http://example.com/@user/
    $valid_url_path = '(?:(?:' . $valid_url_path_characters . '*(?:' . $valid_url_balanced_parens . $valid_url_path_characters . '*)*' . $valid_url_ending_characters . ')|(?:@' . $valid_url_path_characters . '+\/))';

    // Prepare domain name pattern.
    // The ICANN seems to be on track towards accepting more diverse top level
    // domains, so this pattern has been "future-proofed" to allow for TLDs
    // of length 2-64.
    $domain = '(?:[\p{L}\p{M}\p{N}._+-]+\.)?[\p{L}\p{M}]{2,64}\b';
    $ip = '(?:[0-9]{1,3}\.){3}[0-9]{1,3}';
    $auth = '[\p{L}\p{M}\p{N}:%_+*~#?&=.,/;-]+@';
    $trail = '(' . $valid_url_path . '*)?(\\?' . $valid_url_query_chars . '*' . $valid_url_query_ending_chars . ')?';

    // Match absolute URLs.
    $url_pattern = "(?:$auth)?(?:$domain|$ip)/?(?:$trail)?";
    $pattern = "`$url_prefix((?:$protocols)(?:$url_pattern))`u";
    $tasks['replaceFullLinks'] = $pattern;

    // HTML comments need to be handled separately, as they may contain HTML
    // markup, especially a '>'. Therefore, remove all comment contents and add
    // them back later.
    _filter_url_escape_comments('', TRUE);
    $text = preg_replace_callback('`<!--(.*?)-->`s', '_filter_url_escape_comments', $text);

    // Split at all tags; ensures that no tags or attributes are processed.
    $chunks = preg_split('/(<.+?>)/is', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    // PHP ensures that the array consists of alternating delimiters and
    // literals, and begins and ends with a literal (inserting NULL as
    // required). Therefore, the first chunk is always text:
    $chunk_type = 'text';
    // If a tag of $ignore_tags is found, it is stored in $open_tag and only
    // removed when the closing tag is found. Until the closing tag is found,
    // no replacements are made.
    $open_tag = '';

    for ($i = 0; $i < count($chunks); $i++) {
      if ($chunk_type == 'text') {
        // Only process this text if there are no unclosed $ignore_tags.
        if ($open_tag == '') {
          // If there is a match, inject a link into this chunk via the callback
          // function contained in $task.
          $chunks[$i] = preg_replace_callback(
            $pattern,
            function ($match) {
                $info = \Drupal::service('url_embed')->getEmbed(Html::decodeEntities($match[1]));
                if ($info && !empty($info->code->html)) {
                    return '<drupal-url data-embed-url="' . $match[1] . '"></drupal-url>';
                }
              else {
                return $match[1];
              }
            },
            $chunks[$i]
          );
        }
        // Text chunk is done, so next chunk must be a tag.
        $chunk_type = 'tag';
      }
      else {
        // Only process this tag if there are no unclosed $ignore_tags.
        if ($open_tag == '') {
          // Check whether this tag is contained in $ignore_tags.
          if (preg_match("`<($ignore_tags)(?:\s|>)`i", $chunks[$i], $matches)) {
            $open_tag = $matches[1];
          }
        }
        // Otherwise, check whether this is the closing tag for $open_tag.
        else {
          if (preg_match("`<\/$open_tag>`i", $chunks[$i], $matches)) {
            $open_tag = '';
          }
        }
        // Tag chunk is done, so next chunk must be text.
        $chunk_type = 'text';
      }
    }

    $text = implode($chunks);
    // Revert to the original comment contents.
    _filter_url_escape_comments('', FALSE);
    return preg_replace_callback('`<!--(.*?)-->`', '_filter_url_escape_comments', $text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('<p>You can convert plain URLs to &lt;drupal-url&gt; HTML elements. Those elements are later converted to embeds using "Display embedded URLs" text filter.</p>');
    }
    else {
      return $this->t('You can convert plain URLs to embed elements.');
    }
  }

}
