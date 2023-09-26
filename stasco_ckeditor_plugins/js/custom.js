/**
 * @file
 * Node Delete confirmation file.
 */

(function ($) {

  Drupal.behaviors.stascoplugins = {
    attach: function (context, settings) {
      jQuery('[stasco-data-delete-btn]').click(function (event) {
            var submit = confirm("There are incoming links attached to this page. Do you really want to delete this page?");
            if (submit == false) {
            event.preventDefault();
          }
      });
    }
  };

})(jQuery);
