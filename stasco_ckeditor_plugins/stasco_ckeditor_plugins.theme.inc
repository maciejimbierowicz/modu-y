<?php

/**
 * @file
 * Stasco_ckeditor_plugins.theme.inc.
 */

use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Preprocess for stasco list of nodes template.
 *
 * @param array $variables
 *   The list of nodes template.
 */
function template_preprocess_stasco_list_of_nodes(array &$variables) {
  $ids = array_keys($variables['nodes']);
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids);
  if ($nodes) {
    $data = [];
    /** @var \Drupal\node\Entity\Node $node */
    foreach ($nodes as $node) {
      $url = URL::fromRoute('entity.node.canonical', ['node' => $node->id()]);
      $url->setOption('attributes', ['target' => 'blank']);
      $link = Link::fromTextAndUrl($node->getTitle(), $url);
      $data[] = [
        'link' => $link->toString(),
      ];
    }
    $variables['data'] = $data;
  }
}
