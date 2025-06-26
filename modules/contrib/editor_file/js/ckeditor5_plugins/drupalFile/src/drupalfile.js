import {Plugin} from 'ckeditor5/src/core';
import DrupalFileEditing from "./drupalfileediting";
import DrupalEditorFileUploadAction from './ui/drupaluploadactionui';

/**
 * @private
 */
class DrupalFile extends Plugin {
    /**
     * @inheritdoc
     */
    static get requires() {
        return [DrupalFileEditing, DrupalEditorFileUploadAction];
    }

    /**
     * @inheritdoc
     */
    static get pluginName() {
        return 'DrupalFile';
    }
}

export default DrupalFile;
