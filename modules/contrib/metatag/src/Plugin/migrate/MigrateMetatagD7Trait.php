<?php

namespace Drupal\metatag\Plugin\migrate;

use Drupal\Component\Utility\Unicode;
use Drupal\migrate\MigrateException;

/**
 * Provides some helper logic for migrating data from Metatag-D7.
 */
trait MigrateMetatagD7Trait {

  /**
   * Convert meta tags from Metatag-D7 to the new naming convention.
   *
   * Unsupported meta tags will not be converted, only known meta tags.
   *
   * @param array $old_tags
   *   The old data from Metatag-D7, converted into an array.
   *
   * @return array
   *   The data converted to the plugin names required by this version of
   *   Metatag.
   */
  public function transformTags(array $old_tags): array {
    $tags_map = $this->tagsMap();

    $metatags = [];

    foreach ($old_tags as $d7_metatag_name => $metatag_value) {
      // If there's no data for this tag, ignore everything.
      if (empty($metatag_value)) {
        continue;
      }

      // @todo Skip these values for now, maybe some version supported these?
      if (!is_array($metatag_value) || empty($metatag_value['value'])) {
        continue;
      }

      // Convert the D7 meta tag name to the D8 equivalent. If this meta tag
      // is not recognized, skip it.
      if (empty($tags_map[$d7_metatag_name])) {
        continue;
      }
      $d8_metatag_name = $tags_map[$d7_metatag_name];

      // Convert the nested arrays to a flat structure.
      if (is_array($metatag_value['value'])) {
        // Remove empty values.
        $metatag_value['value'] = array_filter($metatag_value['value']);
        // Convert the array into a comma-separated list.
        $metatag_value = implode(', ', $metatag_value['value']);
      }
      else {
        $metatag_value = $metatag_value['value'];
      }
      if (!Unicode::validateUtf8($metatag_value)) {
        $metatag_value = Unicode::convertToUtf8($metatag_value, 'Windows-1252');
      }

      // Keep the entire data structure.
      $metatags[$d8_metatag_name] = $metatag_value;
    }

    // Sort the meta tags alphabetically to make testing easier.
    ksort($metatags);

    return $metatags;
  }

  /**
   * Decode the different versions of encoded values supported by Metatag.
   *
   * Metatag v1 stored data in serialized arrays. Metatag v2 stores data in
   * JSON-encoded strings.
   *
   * @param string $string
   *   The string to decode.
   *
   * @return array
   *   A Metatag values array.
   */
  private function decodeValue($string): array {
    $data = [];

    // Serialized arrays from Metatag v1.
    if (substr($string, 0, 2) === 'a:') {
      $data = @unserialize($string, ['allowed_classes' => FALSE]);
    }

    // Encoded JSON from Metatag v2.
    elseif (substr($string, 0, 2) === '{"') {
      // @todo Handle non-array responses.
      $data = json_decode($string, TRUE);
    }

    // This is expected to be an array, if it isn't then something went wrong.
    if (!is_array($data)) {
      throw new MigrateException('Data from Metatag-D7 was not a serialized array or a JSON-encoded string.');
    }

    return $data;
  }

  /**
   * Match Metatag-D7 meta tags with their D8 counterparts.
   *
   * @return array
   *   An array of D7 tags to their D8 counterparts.
   */
  protected function tagsMap(): array {
    $map = [
      // From the main Metatag module.
      'abstract' => 'abstract',
      'cache-control' => 'cache_control',
      'canonical' => 'canonical_url',
      'description' => 'description',
      'expires' => 'expires',
      'generator' => 'generator',
      'geo.placename' => 'geo_placename',
      'geo.position' => 'geo_position',
      'geo.region' => 'geo_region',
      'icbm' => 'icbm',
      'image_src' => 'image_src',
      'keywords' => 'keywords',
      'next' => 'next',
      'original-source' => 'original_source',
      'pragma' => 'pragma',
      'prev' => 'prev',
      'rating' => 'rating',
      'referrer' => 'referrer',
      'refresh' => 'refresh',
      'revisit-after' => 'revisit_after',
      'rights' => 'rights',
      'robots' => 'robots',
      'set_cookie' => 'set_cookie',
      'shortlink' => 'shortlink',
      'title' => 'title',

      // From metatag_app_links.metatag.inc:
      'al:android:app_name' => 'al_android_app_name',
      'al:android:class' => 'al_android_class',
      'al:android:package' => 'al_android_package',
      'al:android:url' => 'al_android_url',
      'al:ios:app_name' => 'al_ios_app_name',
      'al:ios:app_store_id' => 'al_ios_app_store_id',
      'al:ios:url' => 'al_ios_url',
      'al:ipad:app_name' => 'al_ipad_app_name',
      'al:ipad:app_store_id' => 'al_ipad_app_store_id',
      'al:ipad:url' => 'al_ipad_url',
      'al:iphone:app_name' => 'al_iphone_app_name',
      'al:iphone:app_store_id' => 'al_iphone_app_store_id',
      'al:iphone:url' => 'al_iphone_url',
      'al:web:should_fallback' => 'al_web_should_fallback',
      'al:web:url' => 'al_web_url',
      'al:windows:app_id' => 'al_windows_app_id',
      'al:windows:app_name' => 'al_windows_app_name',
      'al:windows:url' => 'al_windows_url',
      'al:windows_phone:app_id' => 'al_windows_phone_app_id',
      'al:windows_phone:app_name' => 'al_windows_phone_app_name',
      'al:windows_phone:url' => 'al_windows_phone_url',
      'al:windows_universal:app_id' => 'al_windows_universal_app_id',
      'al:windows_universal:app_name' => 'al_windows_universal_app_name',
      'al:windows_universal:url' => 'al_windows_universal_url',

      // From metatag_dc.metatag.inc:
      'dcterms.contributor' => 'dcterms_contributor',
      'dcterms.coverage' => 'dcterms_coverage',
      'dcterms.creator' => 'dcterms_creator',
      'dcterms.date' => 'dcterms_date',
      'dcterms.description' => 'dcterms_description',
      'dcterms.format' => 'dcterms_format',
      'dcterms.identifier' => 'dcterms_identifier',
      'dcterms.language' => 'dcterms_language',
      'dcterms.publisher' => 'dcterms_publisher',
      'dcterms.relation' => 'dcterms_relation',
      'dcterms.rights' => 'dcterms_rights',
      'dcterms.source' => 'dcterms_source',
      'dcterms.subject' => 'dcterms_subject',
      'dcterms.title' => 'dcterms_title',
      'dcterms.type' => 'dcterms_type',

      // From metatag_dc_advanced.metatag.inc:
      'dcterms.abstract' => 'dcterms_abstract',
      'dcterms.accessRights' => 'dcterms_access_rights',
      'dcterms.accrualMethod' => 'dcterms_accrual_method',
      'dcterms.accrualPeriodicity' => 'dcterms_accrual_periodicity',
      'dcterms.accrualPolicy' => 'dcterms_accrual_policy',
      'dcterms.alternative' => 'dcterms_alternative',
      'dcterms.audience' => 'dcterms_audience',
      'dcterms.available' => 'dcterms_available',
      'dcterms.bibliographicCitation' => 'dcterms_bibliographic_citation',
      'dcterms.conformsTo' => 'dcterms_conforms_to',
      'dcterms.created' => 'dcterms_created',
      'dcterms.dateAccepted' => 'dcterms_date_accepted',
      'dcterms.dateCopyrighted' => 'dcterms_date_copyrighted',
      'dcterms.dateSubmitted' => 'dcterms_date_submitted',
      'dcterms.educationLevel' => 'dcterms_education_level',
      'dcterms.extent' => 'dcterms_extent',
      'dcterms.hasFormat' => 'dcterms_has_format',
      'dcterms.hasPart' => 'dcterms_has_part',
      'dcterms.hasVersion' => 'dcterms_has_version',
      'dcterms.instructionalMethod' => 'dcterms_instructional_method',
      'dcterms.isFormatOf' => 'dcterms_is_format_of',
      'dcterms.isPartOf' => 'dcterms_is_part_of',
      'dcterms.isReferencedBy' => 'dcterms_is_referenced_by',
      'dcterms.isReplacedBy' => 'dcterms_is_replaced_by',
      'dcterms.isRequiredBy' => 'dcterms_is_required_by',
      'dcterms.issued' => 'dcterms_issued',
      'dcterms.isVersionOf' => 'dcterms_is_version_of',
      'dcterms.license' => 'dcterms_license',
      'dcterms.mediator' => 'dcterms_mediator',
      'dcterms.medium' => 'dcterms_medium',
      'dcterms.modified' => 'dcterms_modified',
      'dcterms.provenance' => 'dcterms_provenance',
      'dcterms.references' => 'dcterms_references',
      'dcterms.replaces' => 'dcterms_replaces',
      'dcterms.requires' => 'dcterms_requires',
      'dcterms.rightsHolder' => 'dcterms_rights_holder',
      'dcterms.spatial' => 'dcterms_spatial',
      'dcterms.tableOfContents' => 'dcterms_table_of_contents',
      'dcterms.temporal' => 'dcterms_temporal',
      'dcterms.valid' => 'dcterms_valid',

      // From metatag_facebook.metatag.inc:
      'fb:admins' => 'fb_admins',
      'fb:app_id' => 'fb_app_id',
      'fb:pages' => 'fb_pages',

      // From metatag_favicons.metatag.inc:
      'apple-touch-icon' => 'apple_touch_icon',
      'apple-touch-icon-precomposed' => 'apple_touch_icon_precomposed',
      'apple-touch-icon-precomposed_114x114' => 'apple_touch_icon_precomposed_114x114',
      'apple-touch-icon-precomposed_120x120' => 'apple_touch_icon_precomposed_120x120',
      'apple-touch-icon-precomposed_144x144' => 'apple_touch_icon_precomposed_144x144',
      'apple-touch-icon-precomposed_152x152' => 'apple_touch_icon_precomposed_152x152',
      'apple-touch-icon-precomposed_180x180' => 'apple_touch_icon_precomposed_180x180',
      'apple-touch-icon-precomposed_72x72' => 'apple_touch_icon_precomposed_72x72',
      'apple-touch-icon-precomposed_76x76' => 'apple_touch_icon_precomposed_76x76',
      'apple-touch-icon_114x114' => 'apple_touch_icon_114x114',
      'apple-touch-icon_120x120' => 'apple_touch_icon_120x120',
      'apple-touch-icon_144x144' => 'apple_touch_icon_144x144',
      'apple-touch-icon_152x152' => 'apple_touch_icon_152x152',
      'apple-touch-icon_180x180' => 'apple_touch_icon_180x180',
      'apple-touch-icon_72x72' => 'apple_touch_icon_72x72',
      'apple-touch-icon_76x76' => 'apple_touch_icon_76x76',
      'icon_16x16' => 'icon_16x16',
      'icon_192x192' => 'icon_192x192',
      'icon_32x32' => 'icon_32x32',
      'icon_96x96' => 'icon_96x96',
      'mask-icon' => 'mask-icon',
      'shortcut icon' => 'shortcut_icon',

      // From metatag_google_cse.metatag.inc:
      'audience' => 'audience',
      'department' => 'department',
      'doc_status' => 'doc_status',
      'google_rating' => 'google_rating',
      'thumbnail' => 'thumbnail',

      // From metatag_google_plus.metatag.inc; not doing these, Google+ closed.
      'itemtype' => '',
      'itemprop:name' => '',
      'itemprop:description' => '',
      'itemprop:image' => '',
      'author' => '',
      'publisher' => '',

      // From metatag_hreflang.metatag.inc:
      'hreflang_xdefault' => 'hreflang_xdefault',
      // @todo https://www.drupal.org/project/metatag/issues/3077778
      // 'hreflang_' . $langcode => 'hreflang_per_language',
      // From metatag_mobile.metatag.inc:
      'alternate_handheld' => 'alternate_handheld',
      'android-app-link-alternative' => 'android_app_link_alternative',
      'android-manifest' => 'android_manifest',
      'apple-itunes-app' => 'apple_itunes_app',
      'apple-mobile-web-app-capable' => 'apple_mobile_web_app_capable',
      'apple-mobile-web-app-status-bar-style' => 'apple_mobile_web_app_status_bar_style',
      'apple-mobile-web-app-title' => 'apple_mobile_web_app_title',
      'application-name' => 'application_name',
      'cleartype' => 'cleartype',
      'format-detection' => 'format_detection',
      'HandheldFriendly' => 'handheldfriendly',
      'ios-app-link-alternative' => 'ios_app_link_alternative',
      'MobileOptimized' => 'mobileoptimized',
      'msapplication-allowDomainApiCalls' => 'msapplication_allowDomainApiCalls',
      'msapplication-allowDomainMetaTags' => 'msapplication_allowDomainMetaTags',
      'msapplication-badge' => 'msapplication_badge',
      'msapplication-config' => 'msapplication_config',
      'msapplication-navbutton-color' => 'msapplication_navbutton_color',
      'msapplication-notification' => 'msapplication_notification',
      'msapplication-square150x150logo' => 'msapplication_square150x150logo',
      'msapplication-square310x310logo' => 'msapplication_square310x310logo',
      'msapplication-square70x70logo' => 'msapplication_square70x70logo',
      'msapplication-starturl' => 'msapplication_starturl',
      'msapplication-task' => 'msapplication_task',
      'msapplication-task-separator' => 'msapplication_task_separator',
      'msapplication-tilecolor' => 'msapplication_tilecolor',
      'msapplication-tileimage' => 'msapplication_tileimage',
      'msapplication-tooltip' => 'msapplication_tooltip',
      'msapplication-wide310x150logo' => 'msapplication_wide310x150logo',
      'msapplication-window' => 'msapplication_window',
      'theme-color' => 'theme_color',
      'viewport' => 'viewport',
      'x-ua-compatible' => 'x_ua_compatible',

      // From metatag_opengraph.metatag.inc:
      // https://www.drupal.org/project/metatag/issues/3077782
      'article:author' => 'article_author',
      'article:expiration_time' => 'article_expiration_time',
      'article:modified_time' => 'article_modified_time',
      'article:published_time' => 'article_published_time',
      'article:publisher' => 'article_publisher',
      'article:section' => 'article_section',
      'article:tag' => 'article_tag',
      'book:author' => 'book_author',
      'book:isbn' => 'book_isbn',
      'book:release_date' => 'book_release_date',
      'book:tag' => 'book_tag',
      'og:audio' => 'og_audio',
      'og:audio:secure_url' => 'og_audio_secure_url',
      'og:audio:type' => 'og_audio_type',
      'og:country_name' => 'og_country_name',
      'og:description' => 'og_description',
      'og:determiner' => 'og_determiner',
      'og:email' => 'og_email',
      'og:fax_number' => 'og_fax_number',
      'og:image' => 'og_image',
      // @todo '' => 'og_image_alt',
      'og:image:height' => 'og_image_height',
      'og:image:secure_url' => 'og_image_secure_url',
      'og:image:type' => 'og_image_type',
      'og:image:url' => 'og_image_url',
      'og:image:width' => 'og_image_width',
      'og:latitude' => 'og_latitude',
      'og:locale' => 'og_locale',
      'og:locale:alternate' => 'og_locale_alternative',
      'og:locality' => 'og_locality',
      'og:longitude' => 'og_longitude',
      'og:phone_number' => 'og_phone_number',
      'og:postal_code' => 'og_postal_code',
      'og:region' => 'og_region',
      'og:see_also' => 'og_see_also',
      'og:site_name' => 'og_site_name',
      'og:street_address' => 'og_street_address',
      'og:title' => 'og_title',
      'og:type' => 'og_type',
      'og:updated_time' => 'og_updated_time',
      'og:url' => 'og_url',
      // @todo '' => 'og_video',
      // https://www.drupal.org/project/metatag/issues/3089445
      // @todo '' => 'og_video_duration',
      'og:video:height' => 'og_video_height',
      'og:video:secure_url' => 'og_video_secure_url',
      'og:video:type' => 'og_video_type',
      'og:video:url' => 'og_video_url',
      'og:video:width' => 'og_video_width',
      'profile:first_name' => 'profile_first_name',
      'profile:gender' => 'profile_gender',
      'profile:last_name' => 'profile_last_name',
      'profile:username' => 'profile_username',
      'video:actor' => 'video_actor',
      'video:actor:role' => 'video_actor_role',
      'video:director' => 'video_director',
      // @todo 'video:duration' => '',
      'video:release_date' => 'video_release_date',
      'video:series' => 'video_series',
      'video:tag' => 'video_tag',
      'video:writer' => 'video_writer',

      // From metatag_opengraph_products.metatag.inc:
      'product:price:amount' => 'product_price_amount',
      'product:price:currency' => 'product_price_currency',
      // Not supported in D7.
      // @todo '' => 'product_retailer_item_id,
      // Not yet supported in D9.
      // @todo 'product:availability' => '',
      // @todo 'product:brand' => '',
      // @todo 'product:upc' => '',
      // @todo 'product:ean' => '',
      // @todo 'product:isbn' => '',
      // @todo 'product:plural_title' => '',
      // @todo 'product:retailer' => '',
      // @todo 'product:retailer_title' => '',
      // @todo 'product:retailer_part_no' => '',
      // @todo 'product:mfr_part_no' => '',
      // @todo 'product:size' => '',
      // @todo 'product:product_link' => '',
      // @todo 'product:category' => '',
      // @todo 'product:color' => '',
      // @todo 'product:material' => '',
      // @todo 'product:pattern' => '',
      // @todo 'product:shipping_cost:amount' => '',
      // @todo 'product:shipping_cost:currency' => '',
      // @todo 'product:weight:value' => '',
      // @todo 'product:weight:units' => '',
      // @todo 'product:shipping_weight:value' => '',
      // @todo 'product:shipping_weight:units' => '',
      // @todo 'product:expiration_time' => '',
      // @todo 'product:condition' => '',
      // Pinterest.
      // @todo '' => 'pinterest_id',
      // @todo '' => 'pinterest_description',
      // @todo '' => 'pinterest_nohover',
      // @todo '' => 'pinterest_url',
      // @todo '' => 'pinterest_media',
      // @todo '' => 'pinterest_nopin',
      // @todo '' => 'pinterest_nosearch',
      // From metatag_twitter_cards.metatag.inc:
      'twitter:app:country' => 'twitter_cards_app_store_country',
      'twitter:app:id:googleplay' => 'twitter_cards_app_id_googleplay',
      'twitter:app:id:ipad' => 'twitter_cards_app_id_ipad',
      'twitter:app:id:iphone' => 'twitter_cards_app_id_iphone',
      'twitter:app:name:googleplay' => 'twitter_cards_app_name_googleplay',
      'twitter:app:name:ipad' => 'twitter_cards_app_name_ipad',
      'twitter:app:name:iphone' => 'twitter_cards_app_name_iphone',
      'twitter:app:url:googleplay' => 'twitter_cards_app_url_googleplay',
      'twitter:app:url:ipad' => 'twitter_cards_app_url_ipad',
      'twitter:app:url:iphone' => 'twitter_cards_app_url_iphone',
      'twitter:card' => 'twitter_cards_type',
      'twitter:creator' => 'twitter_cards_creator',
      'twitter:creator:id' => 'twitter_cards_creator_id',
      'twitter:data1' => 'twitter_cards_data1',
      'twitter:data2' => 'twitter_cards_data2',
      'twitter:description' => 'twitter_cards_description',
      'twitter:dnt' => 'twitter_cards_donottrack',
      'twitter:image' => 'twitter_cards_image',
      'twitter:image0' => 'twitter_cards_gallery_image0',
      'twitter:image1' => 'twitter_cards_gallery_image1',
      'twitter:image2' => 'twitter_cards_gallery_image2',
      'twitter:image3' => 'twitter_cards_gallery_image3',
      'twitter:image:alt' => 'twitter_cards_image_alt',
      'twitter:image:height' => 'twitter_cards_image_height',
      'twitter:image:width' => 'twitter_cards_image_width',
      'twitter:label1' => 'twitter_cards_label1',
      'twitter:label2' => 'twitter_cards_label2',
      'twitter:player' => 'twitter_cards_player',
      'twitter:player:height' => 'twitter_cards_player_height',
      'twitter:player:stream' => 'twitter_cards_player_stream',
      'twitter:player:stream:content_type' => 'twitter_cards_player_stream_content_type',
      'twitter:player:width' => 'twitter_cards_player_width',
      'twitter:site' => 'twitter_cards_site',
      'twitter:site:id' => 'twitter_cards_site_id',
      'twitter:title' => 'twitter_cards_title',
      'twitter:url' => 'twitter_cards_page_url',

      // From metatag_verification.metatag.inc:
      'baidu-site-verification' => 'baidu',
      'facebook-domain-verification' => 'facebook_domain_verification',
      'google-site-verification' => 'google_site_verification',
      'msvalidate.01' => 'bing',
      'norton-safeweb-site-verification' => 'norton_safe_web',
      'p:domain_verify' => 'pinterest',
      // @todo '' => 'pocket',
      'yandex-verification' => 'yandex',
    ];

    // Trigger hook_metatag_migrate_metatagd7_tags_map_alter().
    // Allow modules to override tags or the entity used for token replacements.
    \Drupal::service('module_handler')->alter('metatag_migrate_metatagd7_tags_map', $map);

    return $map;
  }

}
