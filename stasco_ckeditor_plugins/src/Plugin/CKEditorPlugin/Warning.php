<?php

namespace Drupal\stasco_ckeditor_plugins\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "warning" plugin.
 *
 * @CKEditorPlugin(
 *   id = "warning",
 *   label = @Translation("Warning"),
 *   module = "stasco_ckeditor_plugins"
 * )
 */
class Warning extends CKEditorPluginBase {

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::isInternal().
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return $this->getModulePath('stasco_ckeditor_plugins') . '/js/plugins/warning/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'Warning' => [
        'label' => $this->t('Warning'),
        'image' => $this->getModulePath('stasco_ckeditor_plugins') . '/js/plugins/warning/icons/warning.png',
      ],
    ];
  }

}
