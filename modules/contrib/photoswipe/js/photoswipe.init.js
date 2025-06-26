(function (Drupal, PhotoSwipeLightbox) {
  /**
   * Initialises photoswipe galleries.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.photoswipe = {
    attach(context, settings) {
      once('photoswipe', '.photoswipe-gallery', context).forEach((gallery) => {
        const lightbox = new PhotoSwipeLightbox({
          // Select the gallery.
          gallerySelector: '.photoswipe-gallery',
          // Only trigger photoswipe on links with the photoswipe class:
          childSelector: 'a.photoswipe',
          // Initialize Photoswipe with our settings:
          // eslint-disable-next-line
          pswpModule: PhotoSwipe,
          ...(settings?.photoswipe?.options || {}),
        });

        // Adds ability to react on photoswipe initialization.
        const event = new CustomEvent('photoswipeLightboxBuild', {
          detail: {
            lightbox,
          },
        });

        gallery.dispatchEvent(event);

        // Initialize.
        lightbox.init();
      });
    },
  };
  // eslint-disable-next-line
})(Drupal, PhotoSwipeLightbox);
