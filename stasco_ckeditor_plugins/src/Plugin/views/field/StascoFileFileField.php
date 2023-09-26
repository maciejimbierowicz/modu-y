<?php

namespace Drupal\stasco_ckeditor_plugins\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * The Stasco file-file field.
 *
 * @ViewsField("stasco_file_file_field")
 */
class StascoFileFileField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $file_info = \Drupal::service('stasco_ckeditor_plugins.upload_file_manager')->getUploadedFileInfo($values->fid);
    return isset($file_info['file']) ? $file_info['file'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing since the field is computed.
  }

}
