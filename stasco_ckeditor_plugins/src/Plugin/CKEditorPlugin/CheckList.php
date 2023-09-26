<?php

namespace Drupal\stasco_ckeditor_plugins\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "checklist" plugin.
 *
 * @CKEditorPlugin(
 *   id = "checklist",
 *   label = @Translation("CheckList"),
 *   module = "stasco_ckeditor_plugins"
 * )
 */
class CheckList extends CKEditorPluginBase {

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
    return $this->getModulePath('stasco_ckeditor_plugins') . '/js/plugins/checklist/plugin.js';
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
      'CheckList' => [
        'label' => $this->t('CheckList'),
        'image' => $this->getModulePath('stasco_ckeditor_plugins') . '/js/plugins/checklist/icons/checklist.png',
      ],
    ];
  }

}
