<?php

namespace Drupal\stasco_ckeditor_plugins\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "caution" plugin.
 *
 * @CKEditorPlugin(
 *   id = "caution",
 *   label = @Translation("Caution"),
 *   module = "stasco_ckeditor_plugins"
 * )
 */
class Caution extends CKEditorPluginBase {

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
    return $this->getModulePath('stasco_ckeditor_plugins') . '/js/plugins/caution/plugin.js';
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
      'Caution' => [
        'label' => $this->t('Caution'),
        'image' => $this->getModulePath('stasco_ckeditor_plugins') . '/js/plugins/caution/icons/caution.png',
      ],
    ];
  }

}
