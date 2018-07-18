/**
 * @file
 * Initialization of Social Share Privacy plugin.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Attaches the Social Share Privacy behaviour to the division.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.sharemessage_socialshareprivacy = {
    attach: function (context) {
      // Set the css_path to null because the CSS is already explicitly loaded.
      // No need to dynamically load it in the JS.
      $('.socialshareprivacy', context).socialSharePrivacy({
        services: drupalSettings.socialshareprivacy_config.services,
        css_path: null
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
