
# README

## About

Uses [Photoswipe](https://photoswipe.com/) to display picture galleries on your
Drupal website. This Javascript lightbox library offers very nice mobile
browsing features (in particular swiping to the next picture)!


## Installation

### Manual Installation

- Require the module, e.g. via composer: "composer require drupal/photoswipe"
- Install the module
- Download the "PhotoSwipe-4.1.3" zip file
- Unzip and place the contents of the unzipped "PhotoSwipe-4.1.3" folder
into "library/photoswipe" folder so that the folder structure is:
"library/photoswipe/dist/photoswipe.js"
- Check the status report for errors

### Alternative composer installation

- Require the module, e.g. via composer: "composer require drupal/photoswipe"
- Install the module
- Enable usage of third-party libraries using composer, see
[here](https://www.drupal.org/docs/develop/using-composer/manage-dependencies#third-party-libraries) for an explanation.
- Require the photoswipe library using
"composer require bower-asset/photoswipe:^4"
- Check your status report

Then simply configure your image fields to use photoswipe as their field display
handler.

Note: If you would like to use the "Photoswipe Responsive" display formatter,
please enable the core "Responsive Image" module first.


## Usage

1. Images in entities:
After adding an image or media entity field to any content type
(e.g. 'article'), you can select 'PhotoSwipe' or 'Photoswipe Responsive' as a
display mode in Structure -> Content types -> MyContentType in the tab
'Manage display'.

2. Images in Views:
To use photoswipe in views you can either change the Row style options "View
mode" to the view mode display formatter, you have the photoswipe display
formatter applied to, or you can add a media / image field, where you set the
'Photoswipe' or 'Photoswipe Responsive' display mode similirar to
"Images in entities".
