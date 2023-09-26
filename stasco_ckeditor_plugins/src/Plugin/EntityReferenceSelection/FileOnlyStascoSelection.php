<?php

namespace Drupal\stasco_ckeditor_plugins\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\file\Plugin\EntityReferenceSelection\FileSelection;

/**
 * Class that implements selection of user uploaded files.
 *
 * @EntityReferenceSelection(
 *   id = "only_stasco",
 *   label = @Translation("Only stasco"),
 *   group = "only_stasco",
 *   weight = 0
 * )
 */
class FileOnlyStascoSelection extends FileSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    $query->condition('status', 1);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $target_type = $this->getConfiguration()['target_type'];

    $query = $this->buildEntityQuery($match, $match_operator);
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    // Exclude files, which didn't register in the stasco_upload_file.
    $stasco_files = \Drupal::service('stasco_ckeditor_plugins.upload_file_manager')->getStascoFiles();
    $updated_result = [];
    foreach ($result as $item) {
      if (in_array($item, $stasco_files)) {
        $updated_result[] = $item;
      }
    }

    $options = [];
    $entities = $this->entityManager->getStorage($target_type)->loadMultiple($updated_result);
    foreach ($entities as $entity_id => $entity) {
      $bundle = $entity->bundle();
      $options[$bundle][$entity_id] = Html::escape($this->entityManager->getTranslationFromContext($entity)->label());
    }

    return $options;
  }

}
