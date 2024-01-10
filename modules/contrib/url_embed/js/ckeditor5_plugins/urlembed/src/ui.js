/**
 * @file Registers the URL embed button to the CKEditor instance(s) and binds functionality to it/them.
 */

import { Plugin } from 'ckeditor5/src/core';
import { ButtonView } from 'ckeditor5/src/ui';
import icon from '../urlembed.svg';

export default class UrlEmbedUI extends Plugin {
	init() {
		const editor = this.editor;
    const options = this.editor.config.get('urlEmbed');
    if (!options) {
      return;
    }
    const { dialogURL, openDialog, dialogSettings = {} } = options;
    if (!dialogURL || typeof openDialog !== 'function') {
      return;
    }

    editor.ui.componentFactory.add('urlembed', (locale) => {
      const command = editor.commands.get('urlembed');
      const buttonView = new ButtonView(locale);

      buttonView.set({
        label: Drupal.t('Url Embed'),
        icon: icon,
        tooltip: true,
      });

      // Bind the state of the button to the command.
      buttonView.bind('isOn', 'isEnabled').to(command, 'value', 'isEnabled');

      this.listenTo(buttonView, 'execute', () => {
        // Check if an existing drupalUrl is selected.
        // If so, populate its `url` value in the form.
        let existing = {}
        const { model } = this.editor;
        const drupalUrlElement = getClosestSelectedDrupalUrlElement(
          model.document.selection,
        );
        if (drupalUrlElement) {
          existing = {
            'url_element': drupalUrlElement.getAttribute('url'),
          };
        }
        openDialog(
        `${dialogURL}?${new URLSearchParams(existing)}`,
          ({ attributes }) => {
            editor.execute('urlembed', attributes);
          },
          dialogSettings,
        );
      });

      return buttonView;
    });
	}
}

/**
 * Gets `drupalUrl` element from selection.
 *
 * @param {module:engine/model/selection~Selection|module:engine/model/documentselection~DocumentSelection} selection
 *   The current selection.
 * @return {module:engine/model/element~Element|null}
 *   The `drupalUrl` element which could be either the current selected an
 *   ancestor of the selection. Returns null if the selection has no Drupal
 *   Url element.
 *
 * @private
 */
export function getClosestSelectedDrupalUrlElement(selection) {
  const selectedElement = selection.getSelectedElement();

  return isDrupalUrl(selectedElement)
    ? selectedElement
    : selection.getFirstPosition().findAncestor('drupalUrl');
}

/**
 * Checks if the provided model element is `drupalMedia`.
 *
 * @param {module:engine/model/element~Element} modelElement
 *   The model element to be checked.
 * @return {boolean}
 *   A boolean indicating if the element is a drupalMedia element.
 *
 * @private
 */
export function isDrupalUrl(modelElement) {
  return !!modelElement && modelElement.is('element', 'drupalUrl');
}
