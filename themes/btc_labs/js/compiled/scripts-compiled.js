/**
 * @file
 * A JavaScript file for the theme.
 *
 * In order for this JavaScript to be loaded on pages, see the instructions in
 * the README.txt next to this file.
 */

// JavaScript should be made compatible with libraries other than jQuery by
// wrapping it with an "anonymous closure". See:
// - https://drupal.org/node/1446420
// - http://www.adequatelygood.com/2010/3/JavaScript-Module-Pattern-In-Depth
(function (Drupal, $) {
  'use strict';

  // To understand behaviors, see https://www.drupal.org/node/2269515
  Drupal.behaviors.btc_labs = {
    attach: function (context, settings) {
      // Enable Photoswipe.
      $('.node-type-gallery .gallery-item').click(function (e) {
        e.preventDefault();
        var item_index = $(this).parents('.field__value').index();
        openPhotoswipe('.node-type-gallery .field--name-field-photos', item_index);
      });

      // Handle page sidebar for desktop
      if ($(window).width() > 1024) {
        if ($('.sf-page .sidebar').length > 0) {
          let sidebar = $('.sf-page .sidebar').detach();
          sidebar.insertBefore($('.sf-page section.content'));
        }
      }

      //down icon scroll
      $(".arrow-down-icon", context).click(function () {
        $('html, body').animate({
          scrollTop: $("section.content").offset().top - 200
        }, 1000);
      });

      //accordion last element
      var $accordion = $(".paragraph--type--accordion");
      if ($accordion.length > 0) {
        $accordion.last().addClass('last');
      }

      //check for accordion
      $(".paragraph--type--accordion", context).click(function () {
        $(this).toggleClass("open");
      });

      //Stay informed bar
      const stayInformed = document.querySelector("#block-stayinformed");
      if (stayInformed) {
        $(".subscribe", context).click(function (e) {
          //click on hidden submit button
          $("#mc-embedded-subscribe", context).trigger('click');
          $("#mc-embedded-subscribe", context).trigger('touchstart');
        });
      }

      //video links
      const videoLink = document.querySelector(".video-link");
      if (videoLink) {
        $(".video-link", context).click(function (e) {
          e.preventDefault();
          lity($(this).attr('href'));
        });
      }

      //form filters
      const form = document.querySelector(".views-exposed-form");
      if (form) {
        //select inputs
        $("select").change(function () {
          $(".views-exposed-form .js-form-submit", context).trigger("click");
          $(".views-exposed-form .js-form-submit", context).trigger("touchstart");
        });

        //text inputs
        form.addEventListener("click", function (e) {
          let target = e.target;
          if (target.classList.contains('js-form-type-textfield')) {
            $(".views-exposed-form .js-form-submit", context).trigger("click");
            $(".views-exposed-form .js-form-submit", context).trigger("touchstart");
          }
        });
      }

      //If previous form submission had advanced settings
      if (window.location.hash == "#advanced-search") {
        $(".group-bottom", context).addClass('open');
      }

      // Special handling for clinical trials form.
      $("[data-drupal-selector='views-exposed-form-clinical-trials-block-listing'] input[type='submit']", context).click(function (e) {
        // Validate the form, if advanced search is enabled.
        if ($(".group-bottom", context).hasClass('open')) {
          // Clear errors.
          var $errorContainer = $('.form-validation-errors');
          var $errorInner = $errorContainer.find(".inner");
          $errorInner.html('');

          // Check if prior treatments are set.
          var ptHasValue = false;
          $("[data-drupal-selector='edit-prior-treatments'] input[type='checkbox']", context).each(function () {
            if ($(this).prop('checked')) {
              ptHasValue = true;
            }
          });

          // Check if prior treatments are set.
          var gfHasValue = false;
          $("[data-drupal-selector='edit-genetic-features'] input[type='checkbox']", context).each(function () {
            if ($(this).prop('checked')) {
              gfHasValue = true;
            }
          });
          if ((!gfHasValue || !ptHasValue) && $(".group-bottom", context).hasClass("open")) {
            $errorInner.prepend('<h3>Please Complete Required Questions</h3>');
            $errorInner.append('Please provide an answer to both the Prior Treatments and Genetic Features questions.');
            $errorInner.append('<a class="btn--primary">Return To Advanced Search</a>');
            $errorContainer.first().addClass("open");
            $errorContainer.find('.btn--primary').click(function () {
              $(this).parent().parent().remove();
            });
            $errorContainer.find('.inner').click(function () {
              $(this).parent().remove();
            });
          } else {
            $(".group-bottom", context).removeClass('open');
            window.location.hash = "block-views-block-clinical-trials-block-listing";
          }
        }
        e.preventDefault();
      });

      //search modal
      const searchForm = document.querySelector(".block-search");
      if (searchForm) {
        //search inputs
        let searchInput = document.querySelector(".search-action__search");
        searchInput.addEventListener("click", function () {
          $(".block-search .form-submit").trigger("click");
          $(".block-search .form-submit").trigger("touchstart");
        });

        //open modal
        let triggerSearch = document.querySelector("#trigger-search");
        triggerSearch.addEventListener("click", function (e) {
          e.preventDefault();
          document.querySelector("body").classList.add("modal-open");
          searchForm.classList.add("open");
        });

        //close modal
        let searchClose = document.querySelector(".search-action__close");
        searchClose.addEventListener("click", function () {
          document.querySelector("body").classList.remove("modal-open");
          searchForm.classList.remove("open");
        });
      }

      // Home slider
      $('.block-views-block-home-carousel-block-1 .item-list ul', context).each(function () {
        $(this).slick({
          adaptiveHeight: true,
          infinite: true,
          slidesToShow: 1,
          slidesToScroll: 1,
          arrows: false,
          dots: true,
          responsive: [{
            breakpoint: 768,
            settings: {
              slidesToShow: 1,
              slidesToScroll: 1
            }
          }]
        });
      });

      // News slider
      $('.block-views-block-news-block-latest .item-list ul', context).each(function () {
        $(this).slick({
          adaptiveHeight: true,
          infinite: true,
          slidesToShow: 3,
          slidesToScroll: 3,
          arrows: false,
          dots: true,
          responsive: [{
            breakpoint: 768,
            settings: {
              slidesToShow: 1,
              slidesToScroll: 1
            }
          }]
        });
      });

      //mobile menu
      const triggerMobileMenu = document.querySelector("#trigger-mobile-menu");
      const mobileMenu = document.querySelector("#mobile-menu");
      triggerMobileMenu.addEventListener("click", function (e) {
        e.preventDefault();
        if (!mobileMenu.classList.contains("open")) {
          $("html, body").animate({
            scrollTop: 0
          }, "slow");
        }
        mobileMenu.classList.toggle("open");
        this.classList.toggle("open");
        document.querySelector("body").classList.toggle("menu-active");
        document.querySelector("#header").classList.toggle("menu-active");
      });

      //mobile-menu-dropdowns
      mobileMenu.addEventListener("click", function (e) {
        if (e.target.classList.contains("menu-item--expanded")) {
          e.target.classList.toggle("open");
        }
      });

      //main menu dropdowns
      const mainMenu = document.querySelector("#main-menu");
      const mainContent = document.querySelector("body");
      mainMenu.addEventListener("mouseenter", function (e) {
        mainContent.classList.add("menu-active");
      });
      mainMenu.addEventListener("mouseleave", function (e) {
        mainContent.classList.remove("menu-active");
      });
      function openPhotoswipe(selector, index) {
        var items = [];
        var pswpElement = document.querySelectorAll('.pswp')[0];
        $(selector).find('.gallery-item').each(function () {
          var source = $(this).find('a').attr('href');
          var width = $(this).attr('data-width');
          var height = $(this).attr('data-height');
          var caption = $(this).find('.gallery-item__caption').html();
          var item = {
            src: source,
            w: width,
            h: height
          };
          if (caption !== undefined) {
            item.title = caption;
          }
          items.push(item);
        });
        var options = {
          // galleryUID: $(selector).attr('data-pswp-uid'),
          index: index
        };

        // Pass data to PhotoSwipe and initialize it
        var gallery = new PhotoSwipe(pswpElement, PhotoSwipeUI_Default, items, options);
        gallery.init();
      }
    }
  };
})(Drupal, jQuery);
//# sourceMappingURL=scripts-compiled.js.map
