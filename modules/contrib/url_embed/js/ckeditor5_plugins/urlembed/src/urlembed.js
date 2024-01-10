import UrlEmbedEditing from './editing';
import UrlEmbedUI from './ui';
import { Plugin } from 'ckeditor5/src/core';

export default class UrlEmbed extends Plugin {

  static get requires() {
    return [UrlEmbedEditing, UrlEmbedUI];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'UrlEmbed';
  }

}
