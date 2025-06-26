/**
 * @file The build process always expects an index.js file. Anything exported
 * here will be recognized by CKEditor 5 as an available plugin. Multiple
 * plugins can be exported in this one file.
 *
 * I.e. this file's purpose is to make plugin(s) discoverable.
 */

import DrupalFile from "./drupalfile";
import DrupalInsertFile from './insertfile/drupalinsertfile';

// @todo test extensively
export default {
  DrupalFile,
  DrupalInsertFile,
};
