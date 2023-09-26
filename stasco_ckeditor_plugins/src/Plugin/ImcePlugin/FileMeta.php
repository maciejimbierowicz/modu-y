<?php

namespace Drupal\stasco_ckeditor_plugins\Plugin\ImcePlugin;

use Drupal\Core\Url;
use Drupal\imce\Imce;
use Drupal\imce\ImceFM;
use Drupal\imce\ImcePluginBase;

/**
 * Defines Stasco Imce File Meta Data plugin.
 *
 * @ImcePlugin(
 *   id = "filemeta",
 *   label = "File Meta Data",
 *   weight = -5,
 *   operations = {
 *     "filemeta" = "opFileMeta"
 *   }
 * )
 */
class FileMeta extends ImcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function permissionInfo() {
    return [
      'file_meta' => $this->t('Edit file meta data'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildPage(array &$page, ImceFM $fm) {
    $check_perm = $fm->hasPermission('file_meta');
    // Check if rename permission exists.
    if ($check_perm) {
      $current_path = \Drupal::service('path.current')->getPath();
      $uploaded_files_path = Url::fromRoute('stasco_ckeditor_plugins.stasco_uploaded_files')->toString();
      // Add the custom properties to JS drupalSettings.
      $page['#attached']['library'][] = 'stasco_ckeditor_plugins/drupal.imce.filemeta';
      $page['#attached']['drupalSettings']['imcePlugins']['fileMeta'] = [
        'showFileMeta' => $current_path === $uploaded_files_path,
      ];
    }
  }

  /**
   * Operation handler: rename.
   *
   * @param \Drupal\imce\ImceFM $fm
   *   Imce File Manager.
   */
  public function opFileMeta(ImceFM $fm) {
    $items = $fm->getSelection();
    foreach ($items as $item) {
      if ($item->type === 'file' && $uri = $item->getUri()) {
        $file = Imce::getFileEntity($uri);
        if ($file) {
          $url = URL::fromRoute('stasco_ckeditor_plugins.edit_uploaded_file', ['fid' => $file->id()]);
          $fm->addResponse('filemeta_redirect', $url->toString());
          break;
        }
      }
    }
  }

}
