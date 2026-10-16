import { Plugin } from 'ckeditor5/src/core';
import { ButtonView } from 'ckeditor5/src/ui';
import {findAttributeRange} from "ckeditor5/src/typing";
import iconEdit from "../../../../../icons/file_edit.svg";
import iconUnlink from "../../../../../icons/file_unlink.svg";

export default class DrupalEditorFileUploadActionUi extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return ['LinkEditing', 'LinkUI', 'FileUploadEditing'];
  }

  /**
   * @inheritDoc
   */
  static get pluginName() {
    return 'DrupalEditorFileUploadActionUi';
  }

  init() {
    const { editor } = this;
    this.linkUI = editor.plugins.get('LinkUI');

    editor.ui.componentFactory.add('drupalFileLinkEdit', (locale) => {
      const button = new ButtonView(locale);
      const drupalFileCommand = editor.commands.get('insertFileToEditor');

      button.set({
        icon: iconEdit,
        label: Drupal.t('Edit File'),
        tooltip: true,
      });

      button.bind('isEnabled').to(drupalFileCommand, 'isEnabled');
      button.bind('isVisible').to(drupalFileCommand, 'value');

      button.on('execute', this.openEditingDialog.bind(this));

      return button;
    });

    editor.ui.componentFactory.add('drupalFileLinkUnlink', (locale) => {
      const button = new ButtonView(locale);
      const drupalFileCommand = editor.commands.get('insertFileToEditor');

      button.set({
        icon: iconUnlink,
        label: Drupal.t('Unlink File'),
        tooltip: true,
      });

      button.bind('isEnabled').to(drupalFileCommand, 'isEnabled');
      button.bind('isVisible').to(drupalFileCommand, 'value');

      button.on('execute', this.unlinkFile.bind(this));

      return button;
    });
  }

  /**
   * We use afterInit to make sure the initialization of LinkUI
   * is complete, so that toolbarView exists.
   */
  afterInit() {
    this.linkUI.toolbarView.once('render', () => {
      const editFileButton =
        this.editor.ui.componentFactory.create('drupalFileLinkEdit');
      const unlinkFileButton = this.editor.ui.componentFactory.create(
        'drupalFileLinkUnlink',
      );

      // Register and add the buttons in the toolbarView.
      this.linkUI.toolbarView.registerChild(editFileButton);
      this.linkUI.toolbarView.registerChild(unlinkFileButton);
      this.linkUI.toolbarView.items.addMany([editFileButton, unlinkFileButton]);

      // We hide some default LinkUI elements that do not make sense for
      // file links.
      const { locale } = this.editor;
      const editLinkButton = this.linkUI.toolbarView.items.find(
        (item) => item.label === locale.t('Edit link'),
      );
      const unlinkButton = this.linkUI.toolbarView.items.find(
        (item) => item.label === locale.t('Unlink'),
      );

      const drupalFileCommand = this.editor.commands.get('insertFileToEditor');
      editLinkButton
        .bind('isVisible')
        .to(drupalFileCommand, 'value', (value) => value === false);
      unlinkButton
        .bind('isVisible')
        .to(drupalFileCommand, 'value', (value) => value === false);
    });
  }

  unlinkFile() {
    const attrMaps = {
      linkHref: 'href',
      fileDataEntityType: 'data-entity-type',
      fileDataEntityUuid: 'data-entity-uuid',
    };
    const { model } = this.editor;
    const { selection } = model.document;

    model.change((writer) => {
      // Get ranges to unlink.
      const rangesToUnlink = selection.isCollapsed
        ? [
          findAttributeRange(
            selection.getFirstPosition(),
            "linkHref",
            selection.getAttribute('linkHref'),
            model,
          ),
        ]
        : model.schema.getValidRanges(selection.getRanges(), 'linkHref');

      // Remove attributes from specified ranges.
      for (const range of rangesToUnlink) {
        Object.entries(attrMaps).forEach(([attr, key]) => {
          writer.removeAttribute(attr, range);
        });
      }
    });
  }

  /**
   * Opens file uploading form when the editing button is clicked.
   */
  openEditingDialog() {
    const { editor } = this;
    const selectedLinkElement = this.linkUI._getSelectedLinkElement();

    if (!selectedLinkElement) {
      return;
    }

    const existingValues = selectedLinkElement.hasAttribute('data-entity-uuid')
      ? {
          "data-entity-uuid":
            selectedLinkElement.getAttribute('data-entity-uuid'),
          "data-entity-type":
            selectedLinkElement.getAttribute('data-entity-type'),
        }
      : {};

    const options = editor.config.get('drupalFileUpload');
    const DrupalInsertFile = editor.plugins.get('DrupalInsertFile');

    DrupalInsertFile.constructor.openDialog(
      Drupal.url(`editor_file/dialog/file/${options.format}`),
      existingValues,
      ({ attributes }) => {
        editor.execute('insertFileToEditor', attributes);
      },
    );
  }
}
