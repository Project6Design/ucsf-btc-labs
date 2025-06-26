(function (Drupal, PhotoSwipeDynamicCaption) {
  /**
   * Adds caption plugin to the photoswipe.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.photoswipeCaption = {
    /**
     * Adds caption plugin to the photoswipe.
     */
    attach(context, settings) {
      const captionOptions =
        settings?.photoswipe?.options?.captionOptions || {};

      // Attaches caption plugin.
      once('photoswipeCaption', '.photoswipe-gallery', context).forEach(
        (gallery) => {
          gallery.addEventListener('photoswipeLightboxBuild', (e) => {
            const lightbox = e.detail.lightbox;

            new PhotoSwipeDynamicCaption(lightbox, {
              captionContent: (slide) => {
                return slide.data.element.getAttribute('data-overlay-title');
              },
              ...captionOptions,
            });
          });
        },
      );
    },
  };
  // eslint-disable-next-line
})(Drupal, PhotoSwipeDynamicCaption);
