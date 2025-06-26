/**
 * @file
 * CKEditor 'drupalfile' plugin admin behavior.
 *
 * @deprecated in editor_file:2.0.0 and is removed from editor_file:2.1.0. This
 *  code is only used for the now unsupported CKEditor4 plugin.
 * @see https://www.drupal.org/project/editor_file/issues/3415204
 */

(function ($, Drupal, drupalSettings) {

  "use strict";

  /**
   * Provides the summary for the "drupalfile" plugin settings vertical tab.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behaviour to the "drupalfile" settings vertical tab.
   */
  Drupal.behaviors.ckeditorDrupalFileSettingsSummary = {
    attach: function () {
      $('[data-ckeditor-plugin-id="drupalfile"]').drupalSetSummary(function (context) {
        var root = 'input[name="editor[settings][plugins][drupalfile][file_upload]';
        var $status = $(root + '[status]"]');
        var $extensions = $(root + '[extensions]"]');
        var $maxFileSize = $(root + '[max_size]"]');
        var $scheme = $(root + '[scheme]"]:checked');

        var extensions = $extensions.val() ? $extensions.val().split(', ').length : 1;
        var maxFileSize = $maxFileSize.val() ? $maxFileSize.val() : $maxFileSize.attr('placeholder');

        if (!$status.is(':checked')) {
          return Drupal.t('Uploads disabled');
        }

        var output = '';
        output += Drupal.t('Uploads enabled, max size: @size, @extensions allowed extension(s)', {'@size': maxFileSize, '@extensions': extensions});
        if ($scheme.length) {
          output += '<br />' + $scheme.attr('data-label');
        }
        return output;
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
