<?php

namespace Drupal\stasco_ckeditor_plugins\Plugin\Linkit\Matcher;

use Drupal\linkit\Plugin\Linkit\Matcher\FileMatcher;

/**
 * Stasco File Matcher.
 *
 * @Matcher(
 *   id = "stasco_file",
 *   target_entity = "file",
 *   label = @Translation("Stasco File"),
 *   provider = "file"
 * )
 */
class StascoFileMatcher extends FileMatcher {

  /**
   * {@inheritdoc}
   */
  public function getMatches($string) {
    $query = $this->buildEntityQuery($string)->accessCheck();
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

    $matches = [];
    $entities = $this->entityManager->getStorage($this->target_type)->loadMultiple($updated_result);

    foreach ($entities as $entity) {
      // Check the access against the defined entity access handler.
      /** @var \Drupal\Core\Access\AccessResultInterface $access */
      $access = $entity->access('view', $this->currentUser, TRUE);
      if (!$access->isAllowed()) {
        continue;
      }

      $matches[] = [
        'title' => $this->buildLabel($entity),
        'description' => $this->buildDescription($entity),
        'path' => $this->buildPath($entity),
        'group' => $this->buildGroup($entity),
      ];
    }

    return $matches;
  }

}
