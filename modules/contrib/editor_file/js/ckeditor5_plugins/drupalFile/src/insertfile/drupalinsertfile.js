import {Plugin} from 'ckeditor5/src/core';
import icon from '../../../../../icons/file.svg';
import {ButtonView} from "ckeditor5/src/ui";

/**
 * Provides a toolbar item for inserting files.
 *
 * @private
 * @see DrupalInsertImage.
 */
class DrupalInsertFile extends Plugin {
    /**
     * @inheritdoc
     */
    static get pluginName() {
      return 'DrupalInsertFile';
    }

    /**
     * @inheritdoc
     */
    init() {
        const {editor} = this;
        const options = editor.config.get('drupalFileUpload');

        // This is copied from Linkit module to get rid of "TypeError: this.linkUI.actionsView is null" when Linkit is
        // not enabled.
        // TRICKY: Work-around until the CKEditor team offers a better solution: force the ContextualBalloon to get instantiated early thanks to DrupalImage not yet being optimized like https://github.com/ckeditor/ckeditor5/commit/c276c45a934e4ad7c2a8ccd0bd9a01f6442d4cd3#diff-1753317a1a0b947ca8b66581b533616a5309f6d4236a527b9d21ba03e13a78d8.
        if (this.editor.plugins.get('LinkUI')._createViews) {
          editor.plugins.get('LinkUI')._createViews();
        }

        editor.ui.componentFactory.add('drupalInsertFile', (locale) => {

            const button = new ButtonView(this.locale);
            const drupalFileCommand = editor.commands.get('insertFileToEditor');

            button.set({
              icon,
              label: Drupal.t('Insert file'),
              tooltip: true
            });

            button.bind('isOn').to(drupalFileCommand, 'value');
            button.bind('isEnabled').to(drupalFileCommand, 'isEnabled');

            button.on( 'execute', () => {
              const linkUi = editor.plugins.get('LinkUI');
              // Check if a link element is currently selected.
              const selectedLinkElement = linkUi._getSelectedLinkElement();
              let existingAttributes = {};

              if (selectedLinkElement) {
                existingAttributes = {
                  'data-entity-uuid': selectedLinkElement.getAttribute('data-entity-uuid'),
                  'data-entity-type': selectedLinkElement.getAttribute('data-entity-type'),
                };
              }

              this.openDialog(
                Drupal.url('editor_file/dialog/file/' + options.format),
                existingAttributes,
                ({attributes}) => {
                  editor.execute('insertFileToEditor', attributes);
                }
              )
            });

            return button;
        });
    }

  /**
   * This file is adopted from drupal's ckeditor5.js file due to an issue where
   * the "editor_object" isn't passed to the ajax request.
   *
   * See https://www.drupal.org/project/drupal/issues/3303191
   *
   * @param {string} url
   *   The URL that contains the contents of the dialog.
   * @param {object} existingValues
   *   Existing values that will be sent via POST to the url for the dialog
   *   contents.
   * @param {function} saveCallback
   *   A function to be called upon saving the dialog.
   * @param {object} dialogSettings
   *   An object containing settings to be passed to the jQuery UI.
   */
    openDialog(url, existingValues, saveCallback, dialogSettings = {}) {
      // Add a consistent dialog class.
      const classes = dialogSettings.dialogClass
        ? dialogSettings.dialogClass.split(' ')
        : [];
      classes.push('ui-dialog--narrow');
      dialogSettings.dialogClass = classes.join(' ');
      dialogSettings.autoResize =
        window.matchMedia('(min-width: 600px)').matches;
      dialogSettings.width = 'auto';

      const ckeditorAjaxDialog = Drupal.ajax({
        dialog: dialogSettings,
        dialogType: 'modal',
        selector: '.ckeditor5-dialog-loading-link',
        url,
        progress: { type: 'fullscreen' },
        submit: {
          editor_object: existingValues,
        },
      });
      ckeditorAjaxDialog.execute();

      // Store the save callback to be executed when this dialog is closed.
      Drupal.ckeditor5.saveCallback = saveCallback;
    }
}

export default DrupalInsertFile;
