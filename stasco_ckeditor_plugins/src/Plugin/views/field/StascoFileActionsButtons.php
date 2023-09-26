<?php

namespace Drupal\stasco_ckeditor_plugins\Plugin\views\field;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * The Stasco file actions buttons.
 *
 * @ViewsField("stasco_file_actions_buttons").
 */
class StascoFileActionsButtons extends FieldPluginBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $form['actions']['extra_actions'] = [
      '#type' => 'dropbutton',
      '#links' => [
        'edit' => [
          'title' => $this->t('Edit'),
          'url' => Url::fromRoute('stasco_ckeditor_plugins.edit_uploaded_file', ['fid' => $values->fid]),
        ],
        'delete' => [
          'title' => $this->t('Delete'),
          'url' => Url::fromRoute('stasco_ckeditor_plugins.delete_uploaded_file', ['fid' => $values->fid]),
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing since the field is computed.
  }

}
