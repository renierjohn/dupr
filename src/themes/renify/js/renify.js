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

} (Drupal, jQuery));
