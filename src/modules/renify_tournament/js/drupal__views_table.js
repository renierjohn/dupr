
(function (Drupal, drupalSettings, $) {
  Drupal.behaviors.blockAnonymousAjax = {
    attach: function (context) {
      if (drupalSettings.user.uid == 0) {
        $('[data-dialog-type="modal"]').attr('href', '#')
      }
    }
  };
})(Drupal, drupalSettings, jQuery);



(function (Drupal, drupalSettings, $) {
  Drupal.behaviors.setScoreStatus = {
    attach: function (context) {
      $('.view-display-id-attachment_1 table tbody tr').each((i, el) => {
        const score_a = Number($(el).find('.match-score-a').html());
        const score_b = Number($(el).find('.match-score-b').html());

        if (score_a > 0 || score_b > 0) {
          $(el).addClass('active');
        }

        if (score_a > score_b) {
          $(el).find('.views-field-field-match-pairings').addClass('score_a');
          $(el).find('.views-field-field-match-score-a').css('font-weight', 'bold');
        }
        if (score_a < score_b) {
          $(el).find('.views-field-field-match-pairings').addClass('score_b');
          $(el).find('.views-field-field-match-score-b').css('font-weight', 'bold');
        }
      })
    }
  };
})(Drupal, drupalSettings, jQuery);
