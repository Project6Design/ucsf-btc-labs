/**
 * @file
 * Ensures that AddThis is visible in dialogs and other ajax elements.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Helper function for initialization of Share Message elements.
   *
   * @param {jQuery} $elements
   *   jQuery object that is holding Share Message elements.
   */
  var initElements = function ($elements) {
    $elements.each(function () {

      /* global addthis:true */
      addthis.toolbox(this);
    });
  };

  /**
   * Attaches the Share Message behaviour inside dialogs.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.addthis = {
    attach: function (context) {
      var $sharemessageElements = $(context).find('.addthis_toolbox');

      if ($sharemessageElements.length === 0) {
        return;
      }

      // This is used for special cases when the scripts are added using AJAX,
      // for example if Share Message is rendered in a dialog. In that case
      // the AddThis library is loaded after Share Message.
      if (typeof addthis === 'undefined') {
        var interval = setInterval(function () {
          if (typeof addthis !== 'undefined') {
            clearInterval(interval);
            initElements($sharemessageElements);
          }
        }, 50);
      }
      else {
        initElements($sharemessageElements);
      }
    }
  };

})(jQuery, Drupal);
