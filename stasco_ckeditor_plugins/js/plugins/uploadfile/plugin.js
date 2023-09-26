/**
 * @file
 * Upload file plugin.
 *
 * @ignore
 */

(function ($, Drupal, CKEDITOR) {
  'use strict';

  CKEDITOR.plugins.add('uploadfile', {
    icons: 'uploadfile',

    init: function (editor) {

      editor.addCommand('uploadfile_command', {
        exec: function (editor) {
          var existingValues = {};
          // Prepare a save callback to be used upon saving the dialog.
          var saveCallback = function (returnValues) {
            var uploadedFileHtml = '';
            if (returnValues.markup_or_simple_link === 'markup') {
              uploadedFileHtml = getMarkup(returnValues);
            }
            else if (returnValues.markup_or_simple_link === 'simple_link') {
              uploadedFileHtml = getSimpleLink(returnValues);
            }
            editor.insertHtml(uploadedFileHtml);
          };
          // Drupal.t() will not work inside CKEditor plugins because CKEditor
          // loads the JavaScript file instead of Drupal. Pull translated
          // strings from the plugin settings that are translated server-side.
          var dialogSettings = {
            title: editor.config.uploadfile_dialogTitleAdd,
            dialogClass: 'editor-upload-file-dialog'
          };

          // Open the dialog for the edit form.
          Drupal.ckeditor.openDialog(editor, Drupal.url('stasco_ckeditor_plugins/dialog/uploadfile/' + editor.config.drupal.format), existingValues, saveCallback, dialogSettings);
        }
      });

      editor.on('afterCommandExec', function (evt) {
        if (evt.data.name === 'enter') {
          var element = evt.editor.getSelection().getStartElement();
          if (element && element.is('span') && element.$) {
            var domElement = element.$;
            var attribute = null;
            var attributeName;
            for (var attrIndex = 0; attrIndex < domElement.attributes.length; attrIndex++) {
              attribute = domElement.attributes.item(attrIndex);
              attributeName = attribute.nodeName.toLowerCase();
              if (attributeName === 'stasco_remove_element') {
                element.remove();
                break;
              }
            }
          }
        }
      });

      editor.ui.addButton('UploadFile', {
        label: Drupal.t('UploadFile'),
        command: 'uploadfile_command'
      });
    }
  });

  function getFileMarkup(returnValues) {
    if (returnValues.upload_fieldset.file_info.file) {
      return '<small stasco_download_file="true" class="document-download__file">File: ' +
        returnValues.upload_fieldset.file_info.file +
      ',</small>';
    }
    return '<small stasco_download_file="true" class="document-download__file--hidden">&nbsp;</small>';
  };

  function getCopiesMarkup(returnValues) {
    if (returnValues.upload_fieldset.file_info.copies) {
      return '<small stasco_download_copies="true" class="document-download__copies">Copies: ' +
        returnValues.upload_fieldset.file_info.copies +
      ',</small>';
    }
    return '<small stasco_download_copies="true" class="document-download__copies--hidden">&nbsp;</small>';
  };

  function getRetentionMarkup(returnValues) {
    if (returnValues.upload_fieldset.file_info.retention) {
      return '<small stasco_download_retention="true" class="document-download__retention">Retention: ' +
        returnValues.upload_fieldset.file_info.retention +
      ',</small>';
    }
    return '<small stasco_download_retention="true" class="document-download__retention--hidden">&nbsp;</small>';
  };

  function getVersionMarkup(returnValues) {
    if (returnValues.upload_fieldset.file_info.version) {
      return '<small stasco_download_version="true" class="document-download__version">Version: ' +
        returnValues.upload_fieldset.file_info.version +
      '</small>';
    }
    return '<small stasco_download_version="true" class="document-download__version--hidden">&nbsp;</small>';
  };

  function getMarkup(returnValues) {
    return "<span class=\"view__row view__row--documents-downloads-view\"> \
     <span class=\"block block--document-download-item\"> \
           <span class=\"block__content block__content--document-download-item\"> \
             <span class=\"document-download\"> \
                <a stasco_fid=\"" + returnValues.upload_fieldset.file_info.fid + "\" stasco_markup=\"true\"  class=\"document-download__download-link\" target=\"_blank\" href=\"" + returnValues.upload_fieldset.href + "\"> \
                  <span stasco_download_filename=\"true\" class=\"document-download__title\">" + returnValues.upload_fieldset.file_info.filename + "</span> \
                  <span class=\"document-download__details-container\">" +
                    getFileMarkup(returnValues) +
                    getCopiesMarkup(returnValues) +
                    getRetentionMarkup(returnValues) +
                    getVersionMarkup(returnValues) +
                    "<span class=\"document-download__download-icon\"> \
                      <span class=\"document-download__download-icon--hidden\">&nbsp;</span> \
                    </span> \
                  </span> \
                </a> \
              </span> \
            </span> \
          </span> \
        </span> \
        <span stasco_remove_element=\"true\" class=\"document-download__removable_element--hidden\"> \
          &nbsp; \
        </span>";
  };

  function getSimpleLink(returnValues) {
    return '<a stasco_fid="' + returnValues.upload_fieldset.file_info.fid + '" stasco_simple_link="true" class="" target="_blank" href="' + returnValues.upload_fieldset.href + '">' +
              returnValues.upload_fieldset.file_info.filename +
           '</a>';
  };

})(jQuery, Drupal, CKEDITOR);
