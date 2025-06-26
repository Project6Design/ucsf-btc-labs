# Photoswipe Dynamic Caption
This module provides integration with the
[photoswipe dynamic caption plugin](https://github.com/dimsemenov/photoswipe-dynamic-caption-plugin/tree/main).
Starting from [photoswipe 5](https://photoswipe.com/caption/), there is separate
plugin dedicated to (dynamic) captions.

## Installation

### Composer installation (recommended)

- Enable usage of third-party libraries using composer, see
  [here](https://www.drupal.org/docs/develop/using-composer/manage-dependencies#third-party-libraries)
  for an explanation.
- Install caption library using following composer command: <br>
  `composer require "npm-asset/photoswipe-dynamic-caption-plugin:^1.2"`
- Check your status report

### Manual Installation

- Clone https://github.com/dimsemenov/photoswipe-dynamic-caption-plugin
repository into libraries folder.
- Check the status report for errors

### CDN installation
- Enable "Load PhotoSwipe library from CDN" in the photoswipe settings
(/admin/config/media/photoswipe)
- Check the status report for errors

## Configuration

The module has predefined hardcoded [configuration](./config/install/photoswipe_dynamic_caption.settings.yml).
There are two possible ways of altering configuration:
1. (recommended) Using `hook_photoswipe_js_options_alter` simply put custom
option in `$settings['captionOptions']`. Check
`photoswipe_dynamic_caption_photoswipe_js_options_alter` for details.
2. Override manually configuration file and import it.
