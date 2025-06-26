import {Plugin} from 'ckeditor5/src/core';

import DrupalInsertFile from "./insertfile/drupalinsertfile";
import FileUploadCommand from './fileupload/drupalfilecommand';

/**
 * The editing part of the file upload feature. It registers the `'fileUpload'` command.
 *
 * @extends module:core/plugin~Plugin
 */
export default class FileUploadEditing extends Plugin {

  /**
   * @inheritdoc
   */
  static get requires() {
    return [DrupalInsertFile];
  }

  /**
   * @inheritDoc
   */
  static get pluginName() {
    return 'FileUploadEditing';
  }

  /**
   * @inheritDoc
   */
  init() {
    const editor = this.editor;
    const schema = editor.model.schema;
    const conversion = editor.conversion;

    schema.extend('$text', {
      allowAttributes: [
        'fileDataEntityType',
        'fileDataEntityUuid',
      ],
    });

    // Register fileUpload command.
    editor.commands.add('insertFileToEditor', new FileUploadCommand(editor));

    // Register upcast converters.
    conversion.for('upcast')
      .attributeToAttribute({
        view: {
          name: 'a',
          key: 'data-entity-uuid'
        },
        model: {
          key: 'fileDataEntityUuid',
          value: (viewElement) => {
            // Make sure we do not conflict with other modules like LinkIt, if the type is no file
            // we should not load properties into the model. Another option might be another property like
            // "data-managed-by=editor_file" and load that.
            let entityTypeIsFile = viewElement.getAttribute('data-entity-type') === 'file';
            if (entityTypeIsFile === false) {
              return null;
            }
            return viewElement.getAttribute('data-entity-uuid');
          }
        },
      })
      .attributeToAttribute({
        view: {
          name: 'a',
          key: 'data-entity-type'
        },
        model: {
          key: 'fileDataEntityType',
          value: (viewElement) => {
            // Make sure we do not conflict with other modules like LinkIt, if the type is no file
            // we should not load properties into the model. Another option might be another property like
            // "data-managed-by=editor_file" and load that.
            let entityTypeIsFile = viewElement.getAttribute('data-entity-type') === 'file';
            if (entityTypeIsFile === false) {
              return null;
            }
            return viewElement.getAttribute('data-entity-type');
          }
        },
      });

    conversion.for('downcast')
      .attributeToElement( {
        model: 'fileDataEntityType',
        view: ( attributeValue, {writer} ) => {
          const linkViewElement = writer.createAttributeElement('a', {
            'data-entity-type': attributeValue
          }, { priority: 5 });

          writer.setCustomProperty( 'link', true, linkViewElement );

          return linkViewElement;
        },
      })
      .attributeToElement( {
        model: 'fileDataEntityUuid',
        view: ( attributeValue, {writer} ) => {
          const linkViewElement = writer.createAttributeElement( 'a', {
            'data-entity-uuid': attributeValue
          }, { priority: 5 });

          writer.setCustomProperty( 'link', true, linkViewElement );

          return linkViewElement;
        },
      });
  }

}
