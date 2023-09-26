/**
 * @file
 * Procedure Complete plugin.
 *
 * @ignore
 */

(function ($, Drupal, CKEDITOR) {
  CKEDITOR.plugins.add('procedurecomplete', {
      icons: 'procedurecomplete',

      init: function (editor) {
        editor.addCommand('procedurecomplete_command', {
          exec: function (editor) {
            var procedureCompleteHtml =
              '<a stasco_procedure_complete="true" href="" class="procedure-complete__button" target="_self">' +
                Drupal.t('Procedure complete') +
              '</a>';
            editor.insertHtml(
              procedureCompleteHtml
            );
          }
         });

        editor.ui.addButton('ProcedureComplete', {
          label: Drupal.t('Procedure Complete'),
          command: 'procedurecomplete_command'
        });

        editor.on('afterCommandExec', function (evt) {
          if (evt.data.name === 'enter') {
            var element = evt.editor.getSelection().getStartElement();
            if (element && element.is('div') && element.$) {
              var domElement = element.$;
              if (isProcedureCompleteElement(domElement)) {
                element.remove();
              }
            }
          }
        });
      }
  });

  function isProcedureCompleteElement(domElement) {
    var attribute = null;
    var attributeName;
    for (var attrIndex = 0; attrIndex < domElement.attributes.length; attrIndex++) {
      attribute = domElement.attributes.item(attrIndex);
      attributeName = attribute.nodeName.toLowerCase();
      if (attributeName === 'stasco_procedure_complete') {
        return true;
      }
    }
  }

})(jQuery, Drupal, CKEDITOR);
