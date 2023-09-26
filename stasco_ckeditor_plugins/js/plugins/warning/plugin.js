/**
 * @file
 * Warning plugin.
 *
 * @ignore
 */

(function ($, Drupal, CKEDITOR) {
  CKEDITOR.plugins.add('warning', {
    icons: 'warning',

    init: function (editor) {
      editor.addCommand('warning_command', {
        allowedContent: {
          span: {
            attributes: {},
            classes: {}
          }
        },
        requiredContent: new CKEDITOR.style({
          element: 'span',
          attributes: {
            stasco_warning: ''
          }
        }),
        modes: {wysiwyg: 1},
        canUndo: true,
        exec: function (editor) {
          var element = getSelectedWarning(editor);
          var domElement = null;

          // Set existing values based on selected element.
          var existingValues = {};
          if (element && element.$) {
            domElement = element.$;
            existingValues.text = domElement.innerText || domElement.textContent;
          }
          var dialogSettings = {
            title: Drupal.t('Add Warning'),
            dialogClass: 'editor-warning-dialog'
          };
          // Prepare a save callback to be used upon saving the dialog.
          var saveCallback = function (returnValues) {
            editor.fire('saveSnapshot');

            if (!element) {
              var selection = editor.getSelection();
              var range = selection.getRanges(1)[0];

              if (range.collapsed) {
                var text = new CKEDITOR.dom.text(returnValues.text, editor.document);
                range.insertNode(text);
                range.selectNodeContents(text);
              }

              // Create the new warning by applying a style to the new text.
              var style = new CKEDITOR.style({element: 'span', attributes: {stasco_warning: 'true', class: 'stasco-warning'}});
              style.type = CKEDITOR.STYLE_INLINE;
              style.applyToRange(range);
              range.select();
            }
            else {
              element.setHtml(returnValues.text);
            }

            // Save snapshot for undo support.
            editor.fire('saveSnapshot');
          };
          // Open the dialog for the edit form.
          Drupal.ckeditor.openDialog(editor, Drupal.url('stasco_ckeditor_plugins/dialog/note_caution/' + editor.config.drupal.format), existingValues, saveCallback, dialogSettings);
        }
      });

      editor.on('doubleclick', function (evt) {
        var element = getSelectedWarning(editor) || evt.data.element;
        if (!element.isReadOnly()) {
          if (element && element.$) {
            var domElement = element.$;
            if (isWarningElement(domElement)) {
              editor.getSelection().selectElement(element);
              editor.getCommand('warning_command').exec();
            }
          }
        }
      });

      editor.on('afterCommandExec', function (evt) {
        if (evt.data.name === 'enter') {
          var element = evt.editor.getSelection().getStartElement();
          if (element && element.is('span') && element.$) {
            var domElement = element.$;
            if (isWarningElement(domElement)) {
              element.remove();
            }
          }
        }
      });

      editor.ui.addButton('Warning', {
        label: Drupal.t('Warning'),
        command: 'warning_command'
      });
    }

  });

  function getSelectedWarning(editor) {
    var selection = editor.getSelection();
    var selectedElement = selection.getSelectedElement();
    if (selectedElement && selectedElement.is('span')) {
      return selectedElement;
    }

    var range = selection.getRanges(true)[0];

    if (range) {
      range.shrink(CKEDITOR.SHRINK_TEXT);
      return editor.elementPath(range.getCommonAncestor()).contains('span', 1);
    }
    return null;
  }

  function isWarningElement(domElement) {
    var attribute = null;
    var attributeName;
    for (var attrIndex = 0; attrIndex < domElement.attributes.length; attrIndex++) {
      attribute = domElement.attributes.item(attrIndex);
      attributeName = attribute.nodeName.toLowerCase();
      if (attributeName === 'stasco_warning') {
        return true;
      }
    }
  }

})(jQuery, Drupal, CKEDITOR);
