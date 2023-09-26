<?php

namespace Drupal\stasco_ckeditor_plugins;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * The batch manager.
 */
class BatchManager {

  use StringTranslationTrait;

  /**
   * Batch set.
   *
   * @param mixed $data
   *   The data.
   * @param mixed $op
   *   Options.
   */
  public function updateNodesBatchOperation($data, $op) {
    $batch = [
      'title' => $this->t('Update nodes'),
      'init_message' => $this->t('Starting node update operation.'),
      'progress_message' => $this->t('Completed about @percentage% of the processing nodes.'),
      'error_message' => $this->t('Updating has encountered an error.'),
      'operations' => [
        [
          [self::class, 'batchUpdateNodes'],
          [$data, $op],
        ],
      ],
      'finished' => [
        self::class, 'batchUpdateNodesFinished',
      ],
    ];
    batch_set($batch);
  }

  /**
   * Batch updates nodes.
   *
   * @param mixed $data
   *   The data.
   * @param mixed $op
   *   Options.
   * @param mixed $context
   *   The context.
   */
  public static function batchUpdateNodes($data, $op, &$context) {
    $nodes = $data['nodes'];
    // Persistent data among batch runs.
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['generated'] = 0;
      $context['sandbox']['max'] = count($nodes);
    }

    // Persistent data for results.
    if (!isset($context['results']['generated'])) {
      $context['results']['generated'] = 0;
      $context['results']['not_generated'] = 0;
      $context['results']['start'] = time();
    }

    // We can safely process the static_batch_size nodes at a time without a
    // timeout or out of memory error.
    $config = \Drupal::configFactory()->get('stasco_file_managing.settings');
    $limit = $config->get('batch_node_limit') ?: 25;
    // Reduce the limit for our final batch if we would be processing more
    // than had been requested.
    if ($limit + $context['sandbox']['progress'] > $context['sandbox']['max']) {
      $limit = $context['sandbox']['max'] - $context['sandbox']['progress'];
    }

    if ($context['sandbox']['max'] >= ($context['sandbox']['progress'] + $limit)) {
      $context['sandbox']['progress'] += $limit;
    }
    else {
      $context['sandbox']['progress'] = $context['sandbox']['max'];
    }

    $generated = 0;
    $nodes_for_process = array_slice($nodes, $context['sandbox']['progress'] - $limit, $limit);
    foreach ($nodes_for_process as $node) {
      if ($op == 'edit') {
        UploadFileManager::updateNodeEditingProcess($node, $data['data']);
      }
      elseif ($op == 'replace') {
        UploadFileManager::updateNodeReplacingProcess($node, $data['old_fid'], $data['new_fid'], $data['data']);
      }
      $generated++;
      $context['sandbox']['generated'] += $generated;
    }

    // Display progress message.
    if ($generated > 0) {
      $msg_arguments = [
        '@current' => $context['sandbox']['progress'],
        '@total' => $context['sandbox']['max'],
        '@generated' => $context['sandbox']['generated'],
      ];
      $context['message'] = t('Total updated: @current of @total nodes.&nbsp;', $msg_arguments);
    }

    // Some items couldn't be generated.
    if ($generated !== $limit) {
      $context['sandbox']['not_generated'] += $limit - $generated;
    }

    // Everything has been generated.
    if ($generated === 0 || $context['sandbox']['progress'] >= $context['sandbox']['max']) {
      $context['finished'] = 1;
    }
    else {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }

    // Put the total into the results section when we're finished so we can
    // show it to the admin.
    if ($context['finished']) {
      $context['results']['count'] = $context['sandbox']['progress'];
      $context['results']['generated'] = $context['sandbox']['generated'];
      $context['results']['not generated'] = $context['sandbox']['not generated'];
    }
  }

  /**
   * Batch operation finished callback.
   *
   * @param bool $success
   *   If batch is successful.
   */
  public static function batchUpdateNodesFinished($success) {
    $message = ($success) ? t('All book pages are updated.') : t('Finished with an error.');
    \Drupal::messenger()->addMessage($message);
  }

}
