/**
 * @file
 * Title attribute functions.
 */

(function ($, Drupal, document) {

  'use strict';

  var fieldName = '[name="autotitle"]';

  /**
   * Automatically populate the title attribute.
   */
  $(document).bind('linkit.autocompleteSelect', function (triggerEvent, ui) {
    if (ui.item.hasOwnProperty('label')) {
      $('[data-linkitextended-editor-dialog-form]').find(fieldName).val(ui.item.label);
    }
  });

})(jQuery, Drupal, document);
