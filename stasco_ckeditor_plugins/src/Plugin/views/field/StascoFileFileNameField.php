<?php

namespace Drupal\stasco_ckeditor_plugins\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * The Stasco file-file name field.
 *
 * @ViewsField("stasco_file_file_name_field")
 */
class StascoFileFileNameField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $file_info = \Drupal::service('stasco_ckeditor_plugins.upload_file_manager')->getUploadedFileInfo($values->fid);
    return isset($file_info['filename']) ? $file_info['filename'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing since the field is computed.
  }

}
