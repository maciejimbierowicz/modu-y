<?php

namespace Drupal\stasco_ckeditor_plugins\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "wordpasting" plugin.
 *
 * @CKEditorPlugin(
 *   id = "wordpasting",
 *   label = @Translation("Word Pasting"),
 *   module = "stasco_ckeditor_plugins"
 * )
 */
class WordPasting extends CKEditorPluginBase {

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
    return $this->getModulePath('stasco_ckeditor_plugins') . '/js/plugins/wordpasting/plugin.js';
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
      'WordPasting' => [
        'label' => $this->t('Stasco paste from word'),
        'image' => $this->getModulePath('stasco_ckeditor_plugins') . '/js/plugins/wordpasting/icons/wordpasting.png',
      ],
    ];
  }

}
