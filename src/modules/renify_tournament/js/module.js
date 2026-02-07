(function (Drupal, once) {
  Drupal.behaviors.tournamentGoBackLink = {
    attach: function (context) {
      // 1. Use the new once() function
      // Syntax: once('unique-id', 'selector', context)
      const elements = once('tournament-link-processed', 'a.go-back', context);

      // 2. Iterate over the returned array of elements
      elements.forEach(function (element) {
        // 3. Get the current URL query parameters
        const urlParams = new URLSearchParams(window.location.search);
        const tid = urlParams.get('tid');
        const cid = urlParams.get('cid');

        // 4. Update the href if parameters exist
        if (tid && cid) {
          element.setAttribute('href', `/tournaments/brackets/${tid}/${cid}`);
        }
      });
    }
  };
})(Drupal, once);
