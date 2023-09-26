<?php

namespace Drupal\stasco_ckeditor_plugins\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "note" plugin.
 *
 * @CKEditorPlugin(
 *   id = "note",
 *   label = @Translation("Note"),
 *   module = "stasco_ckeditor_plugins"
 * )
 */
class Note extends CKEditorPluginBase {

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
    return $this->getModulePath('stasco_ckeditor_plugins') . '/js/plugins/note/plugin.js';
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
      'Note' => [
        'label' => $this->t('Note'),
        'image' => $this->getModulePath('stasco_ckeditor_plugins') . '/js/plugins/note/icons/note.png',
      ],
    ];
  }

}
