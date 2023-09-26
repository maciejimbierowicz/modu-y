<?php

namespace Drupal\stasco_ckeditor_plugins\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "procedurecomplete" plugin.
 *
 * @CKEditorPlugin(
 *   id = "procedurecomplete",
 *   label = @Translation("Procedure Complete"),
 *   module = "stasco_ckeditor_plugins"
 * )
 */
class ProcedureComplete extends CKEditorPluginBase {

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
    return $this->getModulePath('stasco_ckeditor_plugins') . '/js/plugins/procedurecomplete/plugin.js';
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
      'ProcedureComplete' => [
        'label' => $this->t('Procedure Complete'),
        'image' => $this->getModulePath('stasco_ckeditor_plugins') . '/js/plugins/procedurecomplete/icons/procedurecomplete.png',
      ],
    ];
  }

}
