/**
 * @file
 * Renify behaviors.
 */
(function (Drupal, $) {

  'use strict';

  Drupal.behaviors.renify = {
    attach (context, settings) {
      // FACETS
      const blockFacet = once('block-facet-once', '.block-facets',context);
      blockFacet.forEach((el) => {
        $(el).click((e) => {
          if($(e.target).parent().hasClass('is-open')) {
            $(e.target).parent().removeClass('is-open');
          } else {
            $(e.target).parent().addClass('is-open');
            console.log($(e.target))
          }
        });
      })
    }
  };

  Drupal.behaviors.duprSearch = {
    attach: function (context, settings) {
      const elements = once('dupr-search-init', '.form-element--api-textfield', context);

      elements.forEach((input) => {
        input.addEventListener('input', debounce(async (e) => {
          const keyword = e.target.value;
          if (!keyword) return;

          try {
            const response = await fetch(`/api/v1/dupr/search?q=${encodeURIComponent(keyword)}`);
            if (response.ok) {
              const data = await response.json();
              console.log('Search Results:', data);
              // Handle UI updates here
            }
          } catch (error) {
            console.error('Search error:', error);
          }
        }, 500));
      });

    }
  };

  const debounce = (func, wait) => {
    let timeout;
    return (...args) => {
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(this, args), wait);
    };
  };

} (Drupal, jQuery));

