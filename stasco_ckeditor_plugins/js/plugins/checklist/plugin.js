/**
 * @file
 * CheckList plugin.
 *
 * @ignore
 */

(function ($, Drupal, CKEDITOR) {
  CKEDITOR.plugins.add('checklist', {
    icons: 'checklist',

    init: function (editor) {
      editor.addCommand('checklist_command', {
        allowedContent: {
          div: {
            attributes: {},
            classes: {}
          }
        },
        requiredContent: new CKEDITOR.style({
          element: 'div',
          attributes: {
            stasco_checklist: ''
          }
        }),
        modes: {wysiwyg: 1},
        canUndo: true,
        exec: function (editor) {
          var element = getSelectedCheckList(editor);
          var domElement = null;

          // Set existing values based on selected element.
          var existingValues = {};
          if (element && element.$) {
            domElement = element.$;
            existingValues.text = getCheckListLabelElement(element).getHtml();
          }
          var dialogSettings = {
            title: Drupal.t('Add CheckList'),
            dialogClass: 'editor-checklist-dialog'
          };
          // Prepare a save callback to be used upon saving the dialog.
          var saveCallback = function (returnValues) {
            editor.fire('saveSnapshot');

            if (!element) {
              var selection = editor.getSelection();

              // Create the new checklist by applying a style to the new text.
              var checkboxId = 'stasco-checkbox-' + Math.random().toFixed(10).split('.')[1];
              var currentElement = selection.getStartElement();
              var html = '<div class="stasco-checklist" stasco_checklist="true">' +
                            '<span class="stasco-checklist__label" stasco_checklist_label="true">' + returnValues.text + '</span>' +
                            '<div class="stasco-checklist__checkbox">' +
                              '<input type="checkbox" class="stasco-checklist__checkbox-input" id="' + checkboxId + '" />' +
                              '<label for="' + checkboxId + '" class="stasco-checklist__checkbox-label"> </label></div></div>';
              element = CKEDITOR.dom.element.createFromHtml(html);
              currentElement.insertBeforeMe(element);
            }
            else {
              getCheckListLabelElement(element).setHtml(returnValues.text);
            }

            // Save snapshot for undo support.
            editor.fire('saveSnapshot');
          };
          // Open the dialog for the edit form.
          Drupal.ckeditor.openDialog(editor, Drupal.url('stasco_ckeditor_plugins/dialog/note_caution/' + editor.config.drupal.format), existingValues, saveCallback, dialogSettings);
        }
      });

      editor.on('doubleclick', function (evt) {
        var element = getSelectedCheckList(editor) || evt.data.element;
        if (!element.isReadOnly()) {
          if (element && element.$) {
            var domElement = element.$;
            if (isCheckListElement(domElement)) {
              editor.getSelection().selectElement(element);
              editor.getCommand('checklist_command').exec();
            }
          }
        }
      });

      editor.on('afterCommandExec', function (evt) {
        if (evt.data.name === 'enter') {
          var element = evt.editor.getSelection().getStartElement();
          if (element && element.is('div') && element.$) {
            var domElement = element.$;
            if (isCheckListElement(domElement)) {
              element.remove();
            }
          }
        }
      });

      editor.ui.addButton('CheckList', {
        label: Drupal.t('CheckList'),
        command: 'checklist_command'
      });
    }

  });

  function getSelectedCheckList(editor) {
    var selection = editor.getSelection();
    var selectedElement = selection.getStartElement();
    if (selectedElement.getAttribute('stasco_checklist_label')) {
      selectedElement = selectedElement.getParent();
    }
    if (selectedElement && selectedElement.getAttribute('stasco_checklist')) {
      return selectedElement;
    }
    return null;
  }

  function getCheckListLabelElement(element) {
    var labelElement = null;
    element.getChildren().toArray().forEach((child) => {
      if (child.getAttribute('stasco_checklist_label')) {
        labelElement = child;
      }
    })
    return labelElement;
  }

  function isCheckListElement(domElement) {
    var attribute = null;
    var attributeName;
    for (var attrIndex = 0; attrIndex < domElement.attributes.length; attrIndex++) {
      attribute = domElement.attributes.item(attrIndex);
      attributeName = attribute.nodeName.toLowerCase();
      if (attributeName === 'stasco_checklist') {
        return true;
      }
    }
  }

})(jQuery, Drupal, CKEDITOR);
