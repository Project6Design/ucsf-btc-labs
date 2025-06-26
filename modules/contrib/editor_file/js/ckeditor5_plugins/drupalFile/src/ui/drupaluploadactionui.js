import {Plugin} from 'ckeditor5/src/core';
import {ButtonView} from 'ckeditor5/src/ui';
import icon from '../../../../../icons/file.svg';

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
    const editor = this.editor;
    this.linkUI = editor.plugins.get('LinkUI');
    this.fileEditButton = this._createEditButton();
  }

  /**
   * We use afterInit to make sure the initialization of LinkUI
   * is complete, so that actionsView exists.
   */
  afterInit() {
    this.linkUI.actionsView.once('render', () => {

      // Render button's template.
      this.fileEditButton.render();

      // Register the button under the link form view, it will handle its destruction.
      this.linkUI.actionsView.registerChild(this.fileEditButton);

      // Inject the element into DOM.
      this.linkUI.actionsView.element.insertBefore(this.fileEditButton.element, this.linkUI.actionsView.unlinkButtonView.element);

      const drupalFileCommand = this.editor.commands.get('insertFileToEditor');

      // Our button must not appear on classic links.
      this.fileEditButton.bind('isVisible').to(drupalFileCommand, 'value');

      // We remove these UI elements which do not make sense for files links.
      this.linkUI.actionsView.editButtonView.bind('isVisible').to(drupalFileCommand, 'value', value => value === false);
      this.linkUI.actionsView.unlinkButtonView.bind('isVisible').to(drupalFileCommand, 'value', value => value === false);

    });

  }

  /**
   * Creates editing button for the file link popup.
   *
   * @returns {*}
   * @private
   */
  _createEditButton() {
    const button = new ButtonView(this.locale);

    button.set({
      icon,
      label: Drupal.t('Edit File'),
      tooltip: true,
    });

    button.on('execute', this.openEditingDialog.bind(this));

    return button;
  }

  /**
   * Opens file uploading form when the editing button is clicked.
   */
  openEditingDialog() {
    const {editor} = this;
    const selectedLinkElement = this.linkUI._getSelectedLinkElement();

    if (!selectedLinkElement) {
      return;
    }

    const existingValues = selectedLinkElement.hasAttribute('data-entity-uuid') ? {
      'data-entity-uuid': selectedLinkElement.getAttribute('data-entity-uuid'),
      'data-entity-type': selectedLinkElement.getAttribute('data-entity-type'),
    } : {};

    const options = editor.config.get('drupalFileUpload');
    const DrupalInsertFile = editor.plugins.get('DrupalInsertFile');

    DrupalInsertFile.openDialog(
      Drupal.url('editor_file/dialog/file/' + options.format),
      existingValues,
      ({attributes}) => {
        editor.execute('insertFileToEditor', attributes);
      }
    );
  }

}
