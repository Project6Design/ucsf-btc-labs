import { Plugin } from 'ckeditor5/src/core';
import { Widget, toWidget } from 'ckeditor5/src/widget';
import UrlEmbedCommand from './command';

export default class UrlEmbedEditing extends Plugin {

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'UrlEmbedEditing';
  }

  /**
   * @inheritdoc
   */
  static get requires() {
    return [Widget];
  }

  /**
   * @inheritdoc
   */
  init() {
    // The "url" attribute will be used to trigger the preview (below).
    this.attrs = {
      url: 'data-embed-url',
      provider: 'data-url-provider',
    };
    const options = this.editor.config.get('urlEmbed');
    if (!options) {
      return;
    }
    this.options = options;
    this.labelError = Drupal.t('Preview failed');
    this.previewError =`
      <p>${Drupal.t(
        'An error occurred while trying to preview the embedded content. Please save your work and reload this page.',
      )}<p>
    `;

    this._defineSchema();
    this._defineConverters();
    this.editor.commands.add(
      'urlembed',
      new UrlEmbedCommand(this.editor),
    );
  }

  /**
   * Registers drupalUrl as a block element in the DOM.
   *
   * @private
   */
  _defineSchema() {
    const schema = this.editor.model.schema;

    schema.register('drupalUrl', {
      isObject: true,
      isContent: true,
      isBlock: true,
      allowWhere: '$block',
      allowAttributes: Object.keys(this.attrs),
    });
    this.editor.editing.view.domConverter.blockElements.push('drupal-url');
  }

  /**
   * Defines handling of drupalUrl elements.
   *
   * @private
   */
  _defineConverters() {
    const {conversion} = this.editor;

    conversion
      .for('upcast')
      .elementToElement({
        model: 'drupalUrl',
        view: {
          name: 'drupal-url',
        },
      });

    // Output should be like:
    // <drupal-url data-embed-url="https://www.youtube.com/watch?v=v26fTGBEi9E" data-url-provider="YouTube"></drupal-url>
    conversion
      .for('dataDowncast')
      .elementToElement({
        model: 'drupalUrl',
        view: {
          name: 'drupal-url',
        },
      });

    // Convert the <drupalUrl> model into an editable <drupal-url> widget.
    conversion
      .for('editingDowncast')
      .elementToElement({
        model: 'drupalUrl',
        view: (modelElement, { writer }) => {
          const container = writer.createContainerElement('figure', {
            class: 'drupal-url',
          });
          writer.setCustomProperty('drupalUrl', true, container);

          return toWidget(container, writer, {
            label: Drupal.t('Url Embed widget'),
          })
        },
      })
      .add((dispatcher) => {
        const converter = (event, data, conversionApi) => {
          const viewWriter = conversionApi.writer;
          const modelElement = data.item;
          const container = conversionApi.mapper.toViewElement(data.item);

          let drupalUrl = this._getPreviewContainer(container.getChildren());
          // Use existing container if it exists, create on if it does not.
          if (drupalUrl) {
            // Stop processing if a preview is already loading.
            if (drupalUrl.getAttribute('data-drupal-url-preview') !== 'ready') {
              return;
            }
            // Preview was ready meaning that a new preview can be loaded.
            // Change the attribute to loading to prepare for the loading of
            // the updated preview. Preview is kept intact so that it remains
            // interactable in the UI until the new preview has been rendered.
            viewWriter.setAttribute(
              'data-drupal-url-preview',
              'loading',
              drupalUrl,
            );
          }
          else {
            drupalUrl = viewWriter.createRawElement('div', {
              'data-drupal-url-preview': 'loading',
            });
            viewWriter.insert(viewWriter.createPositionAt(container, 0), drupalUrl);
          }

          this._loadPreview(modelElement).then(({ label, preview }) => {
            if (!drupalUrl) {
              // Nothing to do if associated preview wrapped no longer exist.
              return;
            }
            // CKEditor 5 doesn't support async view conversion. Therefore, once
            // the promise is fulfilled, the editing view needs to be modified
            // manually.
            this.editor.editing.view.change((writer) => {
              const drupalUrlPreview = writer.createRawElement(
                'div',
                {'data-drupal-url-preview': 'ready', 'aria-label': label},
                (domElement) => {
                  domElement.innerHTML = preview;
                },
              );
              // Insert the new preview before the previous preview element to
              // ensure that the location remains same even if it is wrapped
              // with another element.
              writer.insert(writer.createPositionBefore(drupalUrl), drupalUrlPreview);
              writer.remove(drupalUrl);
            });
          });
        }
        // If the attribute "url" (i.e., 'data-embed-url') is
        // present, try to fetch a preview asynchronously.
        dispatcher.on('attribute:url:drupalUrl', converter);

        return dispatcher;
      });

    // Set attributeToAttribute conversion for all supported attributes.
    Object.keys(this.attrs).forEach((modelKey) => {
      const attributeMapping = {
        model: {
          key: modelKey,
          name: 'drupalUrl',
        },
        view: {
          name: 'drupal-url',
          key: this.attrs[modelKey],
        },
      };
      // Attributes should be rendered only in dataDowncast to avoid having
      // unfiltered data-attributes on the Drupal URL widget.
      conversion.for('dataDowncast').attributeToAttribute(attributeMapping);
      conversion.for('upcast').attributeToAttribute(attributeMapping);
    });
  }

  /**
   * Loads the preview.
   *
   * @param modelElement
   *   The model element which preview should be loaded.
   * @returns {Promise<{preview: string, label: *}|{preview: *, label: string}>}
   *   A promise that returns an object.
   *
   * @private
   *
   * @see DrupalMediaEditing::_fetchPreview().
   */
  async _loadPreview(modelElement) {
    const query = {
      text: this._renderElement(modelElement),
    };
    const response = await fetch(
      Drupal.url('embed/preview/' + this.options.format + '?' + new URLSearchParams(query)),
      {
        headers: {
          'X-Drupal-EmbedPreview-CSRF-Token':
          this.options.previewCsrfToken,
        },
      },
    );

    if (response.ok) {
      const label = Drupal.t('Url Embed widget');
      const preview = await response.text();
      return { label, preview };
    }

    return { label: this.labelError, preview: this.previewError };
  }

  /**
   * Renders the model element.
   *
   * @param modelElement
   *   The drupalMedia model element to be converted.
   * @returns {*}
   *   The model element converted into HTML.
   *
   * @private
   */
  _renderElement(modelElement) {
    // Create model document fragment which contains the model element so that
    // it can be stringified using the dataDowncast.
    const modelDocumentFragment = this.editor.model.change((writer) => {
      const modelDocumentFragment = writer.createDocumentFragment();
      // Create shallow clone of the model element to ensure that the original
      // model element remains untouched and that the caption is not rendered
      // into the preview.
      const clonedModelElement = writer.cloneElement(modelElement, false);
      writer.append(clonedModelElement, modelDocumentFragment);

      return modelDocumentFragment;
    });

    return this.editor.data.stringify(modelDocumentFragment);
  }

  /**
   * Gets the preview container element.
   *
   * @param children
   *   The child elements.
   * @returns {null|*}
   *    The preview child element if available.
   *
   * @private
   */
  _getPreviewContainer(children) {
    for (const child of children) {
      if (child.hasAttribute('data-drupal-url-preview')) {
        return child;
      }

      if (child.childCount) {
        const recursive = this._getPreviewContainer(child.getChildren());
        // Return only if preview container was found within this element's
        // children.
        if (recursive) {
          return recursive;
        }
      }
    }

    return null;
  }

}
