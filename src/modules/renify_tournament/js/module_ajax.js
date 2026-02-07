
(function (Drupal, drupalSettings, $) {
  Drupal.behaviors.blockAnonymousAjax = {
    attach: function (context) {
      if (drupalSettings.user.uid == 0) {
        $('[data-dialog-type="modal"]').attr('href', '#')
      }
    }
  };
})(Drupal, drupalSettings, jQuery);
