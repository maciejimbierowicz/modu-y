/* global imce:true */
(function ($, Drupal, imce, drupalSettings) {
  'use strict';

  /**
   * @var EXIT_METHOD_REDIRECT_URI
   * Constant for exit method type REDIRECT_URI
   */
  var EXIT_METHOD_REDIRECT_URI = 'exitMethod.REDIRECT_URI';

  /**
   * @file
   * Defines Exit File Manager plugin for Imce.
   */

  /**
   * Init handler for Exit File Manager.
   */
  imce.bind('init', imce.exitFileManagerInit = function () {
    var hasPermission = imce.hasPermission('exit_file_manager');
    if (!hasPermission) {
      return undefined;
    }

    // Add toolbar button.
    imce.addTbb('exitfilemanager', {
      title: Drupal.t('Exit'),
      permission: 'exit_file_manager',
      handler: function () {
        imce.exitFileManagerHandler();
      },
      icon: 'cancel'
    });
  });

  /**
   * Handler for Exit File Manager Button.
   */
  imce.exitFileManagerHandler = function () {
    var exitMethod = drupalSettings.imcePlugins.exitFileManager.exitMethod;
    var exitUri = drupalSettings.imcePlugins.exitFileManager.exitUri;
    var exitRedirectToUri = exitMethod === EXIT_METHOD_REDIRECT_URI && exitUri;
    if (exitRedirectToUri) {
      window.location = exitUri;
      return undefined;
    }
    window.close();
  };

})(jQuery, Drupal, imce, drupalSettings);
