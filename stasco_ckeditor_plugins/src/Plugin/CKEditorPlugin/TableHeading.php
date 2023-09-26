<?php

namespace Drupal\stasco_ckeditor_plugins\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "tableheading" plugin.
 *
 * @CKEditorPlugin(
 *   id = "tableheading",
 *   label = @Translation("TableHeading"),
 *   module = "stasco_ckeditor_plugins"
 * )
 */
class TableHeading extends CKEditorPluginBase {

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
    return $this->getModulePath('stasco_ckeditor_plugins') . '/js/plugins/tableheading/plugin.js';
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
      'TableHeading' => [
        'label' => $this->t('Table Heading'),
        'image' => $this->getModulePath('stasco_ckeditor_plugins') . '/js/plugins/tableheading/icons/tableheading.png',
      ],
    ];
  }

}
