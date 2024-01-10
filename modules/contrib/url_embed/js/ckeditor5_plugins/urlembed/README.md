## Development and Stewardship Notes for CKEditor5 plugin
In addition to providing a text format filter that converts URLs into oEmbed content, this module includes a CKEditor5 plugin with a form, in a modal, for inputting the URL.

### Read first
- Drupal's CKEditor 5 architecture: https://api.drupal.org/api/drupal/core!modules!ckeditor5!ckeditor5.api.php/group/ckeditor5_architecture/10.0.x
- Drupal's API for working with CKEditor 5: https://www.drupal.org/docs/drupal-apis/ckeditor-5-api/overview
- "CKEditor 5 Dev Tools" (https://www.drupal.org/project/ckeditor5_dev/) includes a starter template with useful inline comments about naming.

While plugins can be written in plain JavaScript, all documentation uses Typescript. The Typescript files must be compiled for use as a plugin, and require a specific compilation process to be integrated as a standalone plugin within Drupal's CKEditor instance.

Custom plugins can be developed outside of the context of Drupal following the model at [developing custom plugins](https://ckeditor.com/docs/ckeditor5/latest/framework/guides/plugins/creating-simple-plugin-timestamp.html#lets-start). A [custom inspector](https://ckeditor.com/docs/ckeditor5/latest/framework/guides/development-tools.html) to help examine the model and view of the CKEditor interface. **However, for integration within Drupal, the compilation process provided in the generic CKEditor documentation will not work; rather, you need a [DLL build](https://ckeditor.com/docs/ckeditor5/latest/installation/advanced/alternative-setups/dll-builds.html) that will create a standalone plugin.**

### Development
To make modifications to the plugin itself:

1. Add and install this module in a Drupal site as you would normally.
2. cd into `js/ckeditor5_plugins/urlembed`
3. Make desired changes in the `src/` directory.
4. Run the following commands to build the distributable JS:

```
npm install
yarn install
yarn run build
```

5. Run `drush cr`

### Architectural overview

```
├── package.json --> Defines development build tools
├── package-lock.json
├── urlembed.svg --> The toolbar icon
├── webpack.config.js --> Defines the build process
├── src
│   ├── command.js --> The 'controller' for CKEditor actions
│   └── editing.js --> Business logic for output (upcast/downcast)
│   └── index.js --> (Convention) Entrypoint for build
│   └── ui.js --> Handles moving in and out of the Drupal form
│   └── urlembed.js --> Real entrypoint for URL Embed plugin
├── build
│   └── urlembed.js --> The DLL, used by CKEditor
```
