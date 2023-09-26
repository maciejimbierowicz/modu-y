/* global imce:true */
(function ($, Drupal, imce, drupalSettings) {
  'use strict';

  /**
   * @file
   * Defines File Meta plugin for Imce.
   */

  /**
   * Init handler for File Meta.
   */
  imce.bind('init', imce.fileMetaInit = function () {
    var hasPermission = imce.hasPermission('file_meta');
    if (!hasPermission) {
      return undefined;
    }

    // Don't show if custom property set
    var showFileMeta = drupalSettings.imcePlugins.fileMeta.showFileMeta;
    if (!showFileMeta) {
      return undefined;
    }

    // Add toolbar button.
    imce.addTbb('filemeta', {
      title: Drupal.t('Edit'),
      permission: 'file_meta',
      handler: function () {
        imce.fileMetaHandler();
      },
      icon: 'info'
    });
  });

  /**
   * Handler for File Meta Button.
   */
  imce.fileMetaHandler = function () {
    var items = imce.getSelection();
    imce.ajaxItems('filemeta', items, {customComplete: imce.fileMetaAjaxComplete});
  };

  /**
   * Custom ajax complete function.
   */
  imce.fileMetaAjaxComplete = function (xhr, status) {
    var opt = this;
    var response = opt.response;
    if (response.hasOwnProperty('filemeta_redirect')) {
      window.location.href = response.filemeta_redirect;
    }
  };

})(jQuery, Drupal, imce, drupalSettings);
