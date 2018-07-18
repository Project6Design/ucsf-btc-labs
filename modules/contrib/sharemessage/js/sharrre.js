/**
 * @file
 * Initialization of Sharrre plugin.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Attaches the Share Message behaviour to the division.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.sharemessage_sharrre = {
    attach: function (context) {
      var share_services = {};
      $.each(drupalSettings.sharrre_config.services, function (index) {
        share_services[index] = true;
      });

      $('#sharemessage').sharrre({
        share: share_services,
        buttons: {
          googlePlus: {size: 'tall', annotation: 'bubble'},
          facebook: {layout: 'box_count'},
          twitter: {count: 'vertical'},
          digg: {type: 'DiggMedium'},
          delicious: {size: 'tall'},
          stumbleupon: {layout: '5'},
          linkedin: {counter: 'top'},
          pinterest: {layout: 'vertical'}
        },
        url: drupalSettings.sharrre_config.url,
        enableCounter: !!drupalSettings.sharrre_config.enable_counter,
        enableHover: !!drupalSettings.sharrre_config.enable_hover,
        enableTracking: !!drupalSettings.sharrre_config.enable_tracking,
        urlCurl: drupalSettings.sharrre_config.url_curl
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
