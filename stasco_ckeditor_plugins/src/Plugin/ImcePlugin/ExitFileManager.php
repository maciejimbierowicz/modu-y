<?php

namespace Drupal\stasco_ckeditor_plugins\Plugin\ImcePlugin;

use Drupal\Core\Url;
use Drupal\imce\ImceFM;
use Drupal\imce\ImcePluginBase;

/**
 * Defines Stasco Imce Exit plugin.
 *
 * @ImcePlugin(
 *   id = "exit_file_manager",
 *   label = "ExitFileManager",
 *   weight = 20,
 *   operations = {}
 * )
 */
class ExitFileManager extends ImcePluginBase {
  const EXIT_METHOD_REDIRECT_URI = 'exitMethod.REDIRECT_URI';
  const EXIT_METHOD_CLOSE_WINDOW = 'exitMethod.CLOSE_WINDOW';

  /**
   * {@inheritdoc}
   */
  public function permissionInfo() {
    return [
      'exit_file_manager' => $this->t('Exit file manager'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildPage(array &$page, ImceFM $fm) {
    $check_perm = $fm->hasPermission('exit_file_manager');
    // Check if rename permission exists.
    if ($check_perm) {
      $current_path = \Drupal::service('path.current')->getPath();
      $uploaded_files_path = Url::fromRoute('stasco_ckeditor_plugins.stasco_uploaded_files')->toString();
      // Alter the exit method and URI based upon the location.
      $exit_method = self::EXIT_METHOD_CLOSE_WINDOW;
      $exit_uri = NULL;
      if ($current_path === $uploaded_files_path) {
        $exit_method = self::EXIT_METHOD_REDIRECT_URI;
        $exit_uri = Url::fromRoute('stasco.config')->toString();
      }
      // Add the custom properties to JS drupalSettings.
      $page['#attached']['library'][] = 'stasco_ckeditor_plugins/drupal.imce.exitfilemanager';
      $page['#attached']['drupalSettings']['imcePlugins']['exitFileManager'] = [
        'exitMethod' => $exit_method,
        'exitUri' => $exit_uri,
      ];
    }
  }

}
