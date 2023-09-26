<?php

namespace Drupal\stasco_ckeditor_plugins\Plugin\ImcePlugin;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\imce\Imce;
use Drupal\imce\Plugin\ImcePlugin\Delete;

/**
 * Defines Stasco Imce Delete plugin.
 *
 * @ImcePlugin(
 *   id = "delete",
 *   label = "Delete",
 *   weight = 18,
 *   operations = {
 *     "delete" = "opDelete"
 *   }
 * )
 */
class StascoDelete extends Delete {

  /**
   * Deletes a file by uri.
   */
  public static function deleteFileUri($uri, $ignore_usage = FALSE) {
    // Managed file.
    if ($file = Imce::getFileEntity($uri)) {
      $stasco_usage = \Drupal::service('stasco_ckeditor_plugins.upload_file_manager')
        ->isUsingStascoFile($file->id());
      if ($stasco_usage) {
        $url = URL::fromRoute('stasco_ckeditor_plugins.delete_uploaded_file', ['fid' => $file->id()]);
        $link = Link::fromTextAndUrl(t('here'), $url);
        \Drupal::messenger()
          ->addMessage(t('%filename is being used in other pages. Click @here to see usages.', [
            '%filename' => $file->getFilename(),
            '@here' => $link->toString(),
          ]), 'error');
        return FALSE;
      }
      elseif (!$ignore_usage && $usage = \Drupal::service('file.usage')
        ->listUsage($file)) {
        unset($usage['imce']);
        if ($usage) {
          \Drupal::messenger()
            ->addMessage(t('%filename is in use by another application.', ['%filename' => $file->getFilename()]), 'error');
          return FALSE;
        }
      }
      $file->delete();
      return TRUE;
    }
    // Unmanaged file.
    return \Drupal::service('file_system')->delete($uri);
  }

}
