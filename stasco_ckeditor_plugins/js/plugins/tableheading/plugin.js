/**
 * @file
 * TableHeading plugin.
 *
 * @ignore
 */

(function ($, Drupal, CKEDITOR) {
  CKEDITOR.plugins.add('tableheading', {
    icons: 'tableheading',

    init: function (editor) {
      editor.addCommand('tableheading_command', {
        allowedContent: {
          td: {
            attributes: {},
            classes: {}
          }
        },
        requiredContent: new CKEDITOR.style({
          element: 'td',
          attributes: {}
        }),
        modes: {wysiwyg: 1},
        canUndo: true,
        exec: function (editor) {
          var cellList = getSelectedCells(editor);
          cellList.forEach((cell) => {
            if (cell.hasClass('table__cell--selected')) {
              cell.removeClass('table__cell--selected');
            } else {
              cell.addClass('table__cell--selected');
            }
          });
        }
      });

      editor.ui.addButton('TableHeading', {
        label: Drupal.t('Table Heading'),
        command: 'tableheading_command'
      });
    }

  });

  function getSelectedCells(editor) {
    var cellList = [];
    var selection = editor.getSelection();
    var range = selection.getRanges(1)[0];
    var walker = new CKEDITOR.dom.walker(range);
    var element = walker.lastBackward();
    while(element && (!element.is || (!element.is('td') && !element.is('th')))) {
      element = element.getParent();
    }
    if (element) { cellList.push(element);
    }
    walker.reset();
    while(element = walker.next()) {
      if (element.is && (element.is('td') || element.is('th'))) {
        cellList.push(element);
      }
    }
    return cellList;
  }
})(jQuery, Drupal, CKEDITOR);
