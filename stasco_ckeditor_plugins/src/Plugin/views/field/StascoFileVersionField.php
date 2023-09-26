<?php

namespace Drupal\stasco_ckeditor_plugins\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Stasco file version field.
 *
 * @ViewsField("stasco_file_version_field")
 */
class StascoFileVersionField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $file_info = \Drupal::service('stasco_ckeditor_plugins.upload_file_manager')->getUploadedFileInfo($values->fid);
    return isset($file_info['version']) ? $file_info['version'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing since the field is computed.
  }

}
