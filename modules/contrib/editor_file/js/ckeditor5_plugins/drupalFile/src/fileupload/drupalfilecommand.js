import {Command} from 'ckeditor5/src/core';
import {findAttributeRange} from 'ckeditor5/src/typing';
import {first} from 'ckeditor5/src/utils';

class FileUploadCommand extends Command {

  /**
   * @inheritDoc
   */
  refresh() {
    const model = this.editor.model;
    const selection = model.document.selection;
    const selectedElement = selection.getSelectedElement() || first(selection.getSelectedBlocks());

    if (this.isLinkableElement(selectedElement, model.schema)) {
      this.value = selectedElement.getAttribute('fileDataEntityType') === 'file';
      this.isEnabled = model.schema.checkAttribute(selectedElement, 'linkHref');
    } else {
      this.value = selection.getAttribute('fileDataEntityType') === 'file';
      this.isEnabled = model.schema.checkAttributeInSelection(selection, 'linkHref');
    }
  }

  /**
   * Handles command to create/update file link.
   */
  execute(attributes) {
    const editor = this.editor;
    const model = editor.model;

    model.change(writer => {
      this.updateFileLink(writer, model, attributes);
    });
  }

  /**
   * Insert/update file link.
   *
   * @param writer
   * @param model
   * @param attributes
   */
  updateFileLink(writer, model, attributes = {}) {
    const selection = model.document.selection;

    const attrMaps = {
      linkHref: 'href',
      fileDataEntityType: 'data-entity-type',
      fileDataEntityUuid: 'data-entity-uuid',
    };

    // When selection is inside text with `linkHref` attribute.
    if (selection.hasAttribute('linkHref')) {
      // Editing existing file link.
      const position = selection.getFirstPosition();

      Object.entries(attrMaps).forEach(([attr, key]) => {
        const linkRange = findAttributeRange(position, attr, selection.getAttribute(attr), model);
        console.log(linkRange);
        writer.setAttribute(attr, attributes[key], linkRange);
      });
    } else {
      // Check if text is selected.
      let selectedText;
      const range = selection.getFirstRange();

      // eslint-disable-next-line no-restricted-syntax
      for (const item of range.getItems()) {
        selectedText = item.data;
      }

      const linkAttrs = {};

      Object.entries(attrMaps).forEach(([attr, key]) => {
        linkAttrs[attr] = attributes[key];
      });

      const text = !selectedText ? attributes.filename : selectedText;
      const linkedText = writer.createText(text, linkAttrs);

      model.insertContent(linkedText);

      if (linkedText.parent) {
        writer.setSelection(linkedText, 'on');
      }
    }
  }

  /**
   * Returns `true` if the specified `element` can be linked (the element allows the `linkHref` attribute).
   *
   * This function is adopted from the LinkUi plugin.
   *
   * @params {module:engine/model/element~Element|null} element
   * @params {module:engine/model/schema~Schema} schema
   * @returns {Boolean}
   */
  isLinkableElement(element, schema) {
    if (!element) {
      return false;
    }

    return schema.checkAttribute(element.name, 'linkHref');
  }

}

export default FileUploadCommand;
