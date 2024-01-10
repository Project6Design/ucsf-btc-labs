import {Command} from 'ckeditor5/src/core';

export default class UrlEmbedCommand extends Command {

  /**
   * @inheritdoc
   */
  refresh() {
    // Check if there is a current selection, and if it is a `drupalUrl`
    // element. If it is a *different* kind of element, the Url Embed button
    // should be disabled.
    const currentSelection = this.editor.model.document.selection.getSelectedElement();
    if (currentSelection === null || currentSelection.name == 'drupalUrl') {
      this.isEnabled = true;
    }
    else {
      this.isEnabled = false;
    }
  }

  /**
   * Executes the command.
   *
   * @param {Object} attributes
   *   An object with keys 'url' and 'provider'
   */
  execute(attributes) {
    const { model } = this.editor;
    const urlEmbedEditing = this.editor.plugins.get('UrlEmbedEditing');
    // Create object that contains supported data-attributes in view data by
    // flipping `UrlEmbedEditing.attrs` object (i.e. keys from object become
    // values and values from object become keys).
    const dataAttributeMapping = Object.entries(urlEmbedEditing.attrs).reduce(
      (result, [key, value]) => {
        result[value] = key;
        return result;
      },
      {},
    );
    // \Drupal\entity_embed\Form\EntityEmbedDialog returns data in keyed by
    // data-attributes used in view data. This converts data-attribute keys to
    // keys used in model.
    const modelAttributes = Object.keys(attributes).reduce(
      (result, attribute) => {
        if (dataAttributeMapping[attribute]) {
          result[dataAttributeMapping[attribute]] = attributes[attribute];
        }
        return result;
      },
      {},
    );
    model.change((writer) => {
      model.insertContent(
        writer.createElement('drupalUrl', modelAttributes)
      );
    });
  }
}
