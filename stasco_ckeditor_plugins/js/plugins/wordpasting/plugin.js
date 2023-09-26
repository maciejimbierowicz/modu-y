/**
 * @file
 * WordPasting plugin.
 *
 * @ignore
 */

(function ($, Drupal, CKEDITOR) {
  // @vars
  var ALL_SPAN_TAGS_REGEX = /<\/?span[^>]*>/g;
  var ALL_EMPTY_PARAGRAPH_TAGS_REGEX = /<p(?: [^>]*)?>(&nbsp;)*<\/p>/g;
  var INNER_SPAN_TAG_REGEX = /<span(?: [^>]*)?>([^<]*)<\/span>/;
  var INNER_SPAN_TAG_REGEX_GLOBAL = /<span(?: [^>]*)?>([^<]*)<\/span>/g;
  var ALL_TAG_ATTRIBUTES_REGEX_GLOBAL = /<([a-z][a-z0-9]*)[^>]*?(\/?)>/g;
  var FONT_SIZE_ATTRIBUTE_REGEX = /font-size:\s*([0-9]*)/;

  var H1_HOOK_REGEX = /@H1@([^@!]*)@!H1@/g;
  var H2_HOOK_REGEX = /@H2@([^@!]*)@!H2@/g;
  var H3_HOOK_REGEX = /@H3@([^@!]*)@!H3@/g;
  var H4_HOOK_REGEX = /@H4@([^@!]*)@!H4@/g;

  var H1_MIN = 24;
  var H2_MIN = 18;
  var H3_MIN = 16;
  var H4_MIN = 14;

  CKEDITOR.plugins.add('wordpasting', {
    icons: 'wordpasting',

    init: function (editor) {
      editor.addCommand('wordpasting_command', {
        exec: function (editor) {
          var existingValues = {};
          // Prepare a save callback to be used upon saving the dialog.
          var saveCallback = function (returnValues) {
            var parsedHtml = cleanTextCopiedFromWord(returnValues.text);
            parsedHtml +=
              '<span stasco_remove_element="true" class="document-download__removable_element--hidden">&nbsp;</span>';
            editor.insertHtml(parsedHtml);
          };

          var dialogSettings = {
            dialogClass: 'editor-word-pasting-dialog'
          };

          // Open the dialog for the edit form.
          Drupal.ckeditor.openDialog(
            editor,
            Drupal.url(
              'stasco_ckeditor_plugins/dialog/word-pasting/' + editor.config.drupal.format
            ),
            existingValues,
            saveCallback,
            dialogSettings
          );
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

      editor.ui.addButton('WordPasting', {
        label: Drupal.t('Paste from word'),
        command: 'wordpasting_command'
      });
    }
  });

  function cleanTextReplaceInnerSpanWithHeaderHook(cleanText, innerSpanTag) {
    var fontSizeMatch = FONT_SIZE_ATTRIBUTE_REGEX.exec(innerSpanTag);
    var innerSpanTagMatch = INNER_SPAN_TAG_REGEX.exec(innerSpanTag);
    var hasInnerSpanTagMatch = innerSpanTagMatch && innerSpanTagMatch.length > 0;
    if (!hasInnerSpanTagMatch) {
      return cleanText;
    }
    var innerSpanTagContent = innerSpanTagMatch[1];
    var hasFontSizeMatch = fontSizeMatch && fontSizeMatch.length > 0;
    if (hasFontSizeMatch) {
      var fontSize = parseInt(fontSizeMatch[1]);
      var isH1 = fontSize >= H1_MIN;
      if (isH1) {
        return cleanText.replace(
          innerSpanTag,
          '@H1@' + innerSpanTagContent + '@!H1@'
        );
      }
      var isH2 = fontSize >= H2_MIN && fontSize < H1_MIN;
      if (isH2) {
        return cleanText.replace(
          innerSpanTag,
          '@H2@' + innerSpanTagContent + '@!H2@'
        );
      }
      var isH3 = fontSize >= H3_MIN && fontSize < H2_MIN;
      if (isH3) {
        return cleanText.replace(
          innerSpanTag,
          '@H3@' + innerSpanTagContent + '@!H3@'
        );
      }
      var isH4 = fontSize >= H4_MIN && fontSize < H3_MIN;
      if (isH4) {
        return cleanText.replace(
          innerSpanTag,
          '@H4@' + innerSpanTagContent + '@!H4@'
        );
      }
    }
    return cleanText.replace(
      innerSpanTag,
      innerSpanTagContent
    );
  };

  function cleanTextReplaceInnerSpanWithHeaderHooks(cleanText) {
    var innerSpanTags = cleanText.match(INNER_SPAN_TAG_REGEX_GLOBAL);
    if (!innerSpanTags) {
      return cleanText;
    }
    innerSpanTags.forEach(function (innerSpanTag) {
      cleanText = cleanTextReplaceInnerSpanWithHeaderHook(
        cleanText,
        innerSpanTag
      );
    });
    return cleanText;
  };

  function cleanTextReplaceHeaderHooks(cleanText) {
    cleanText = cleanText.replace(H1_HOOK_REGEX, '<h1>$1</h1>');
    cleanText = cleanText.replace(H2_HOOK_REGEX, '<h2>$1</h2>');
    cleanText = cleanText.replace(H3_HOOK_REGEX, '<h3>$1</h3>');
    cleanText = cleanText.replace(H4_HOOK_REGEX, '<h4>$1</h4>');
    return cleanText;
  };

  function cleanTextReplaceAllSpans(cleanText) {
    // NOTE: We remove all <span> as the editor does not use span
    return cleanText.replace(ALL_SPAN_TAGS_REGEX, '');
  };

  function cleanTextReplaceAllEmptyParagraphs(cleanText) {
    // NOTE: We replace all <p> tags as the editor adds these in before it gets input
    return cleanText.replace(ALL_EMPTY_PARAGRAPH_TAGS_REGEX, '');
  };

  function cleanTextReplaceAllAttributes(cleanText) {
    // NOTE: We remove all attributes that have been applied to the html tags
    return cleanText.replace(ALL_TAG_ATTRIBUTES_REGEX_GLOBAL, '<$1$2>');
  };

  function cleanTextCopiedFromWord($text) {
    var hasValidText =
      $text &&
      $text.value &&
      typeof $text.value === 'string' &&
      $text.value.length !== 0;
    if (!hasValidText) {
      return '';
    }
    var cleanText = $text.value;
    cleanText = cleanTextReplaceInnerSpanWithHeaderHooks(cleanText);
    cleanText = cleanTextReplaceAllSpans(cleanText);
    cleanText = cleanTextReplaceAllEmptyParagraphs(cleanText);
    cleanText = cleanTextReplaceHeaderHooks(cleanText);
    cleanText = cleanTextReplaceAllAttributes(cleanText);
    return cleanText;
  }
})(jQuery, Drupal, CKEDITOR);
