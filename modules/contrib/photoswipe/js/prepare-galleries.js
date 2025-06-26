(function (Drupal) {
  /**
   * Adds wrapper for images without it.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.photoswipePrepareGalleries = {
    /**
     * Ensures all photoswipe photos are inside an photoswipe-gallery wrapper.
     */
    attach(context) {
      once('photoswipePrepareGalleries', 'a.photoswipe', context).forEach(
        (element) => {
          if (!element.closest('.photoswipe-gallery')) {
            element.outerHTML = `<span class="photoswipe-gallery photoswipe-gallery--fallback-wrapper">${element.outerHTML}</span>`;
          }
        },
      );
    },
  };
})(Drupal);
