<?php

namespace Drupal\stasco_ckeditor_plugins\Element;

use Drupal\linkit\Element\Linkit;

/**
 * Provides a form element for stasco linkit.
 *
 * @FormElement("stasco_linkit")
 */
class StascoLinkit extends Linkit {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#process'][] = [static::class, 'processAjaxForm'];

    return $info;
  }

}
