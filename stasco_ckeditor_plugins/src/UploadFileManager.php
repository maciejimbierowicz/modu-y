<?php

namespace Drupal\stasco_ckeditor_plugins;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\FileUsage\DatabaseFileUsageBackend;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * The UploadFileManager class.
 *
 * @todo document this part.
 */
class UploadFileManager implements UploadFileManagerInterface {

  use StringTranslationTrait;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Database
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Database file usage backend.
   *
   * @var \Drupal\file\FileUsage\DatabaseFileUsageBackend
   */
  protected $fileUsage;

  /**
   * Batch manager.
   *
   * @var \Drupal\stasco_ckeditor_plugins\BatchManager
   */
  protected $batchManager;

  /**
   * File storage interface.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * The file system interface.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private $fileSystem;

  /**
   * The file url generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * PriceRuleHistory constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\file\FileUsage\DatabaseFileUsageBackend $file_usage
   *   Database file usage backend.
   * @param \Drupal\stasco_ckeditor_plugins\BatchManager $batch_manager
   *   The batch manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger interface.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager, DatabaseFileUsageBackend $file_usage, BatchManager $batch_manager, MessengerInterface $messenger, FileSystemInterface $file_system, FileUrlGeneratorInterface $fileUrlGenerator) {
    $this->database = $database;
    $this->messenger = $messenger;
    $this->fileSystem = $file_system;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUsage = $file_usage;
    $this->batchManager = $batch_manager;
    $this->fileStorage = $this->entityTypeManager->getStorage('file');
    $this->fileUrlGenerator = $fileUrlGenerator;
  }

  /**
   * {@inheritdoc}
   */
  public static function updateNodeEditingProcess($node, $data) {
    $save_node = FALSE;
    $body = $node->get('body')->value;
    $doc = \phpQuery::newDocument($body);
    $markups = pq('a[stasco_fid=' . $data['fid'] . '][stasco_markup]');
    if ($markups->elements) {
      $save_node = TRUE;
      static::editDownloadLinks($markups, $data);
    }
    $simple_links = pq('a[stasco_fid=' . $data['fid'] . '][stasco_simple_link]');
    if ($simple_links->elements) {
      $save_node = TRUE;
      static::editSimpleLinks($simple_links, $data);
    }
    if ($save_node) {
      // Update node's body.
      $updated_body = $doc->html();
      $node->body->value = $updated_body;
      $node->body->format = 'full_html';
      $node->save();
    }
  }

  /**
   * Edit download link.
   *
   * @param \phpQueryObject $links
   *   Links.
   * @param mixed $data
   *   Data.
   */
  protected static function editDownloadLinks(\phpQueryObject $links, $data) {
    $filename = $links->find('[stasco_download_filename]');
    $filename->html($data['filename']);

    // Populate stasco_download_file.
    $file = $links->find('[stasco_download_file]');
    self::editDownloadLinkShowOrHideDetail($file, $data['file']);
    $file->html('File: ' . $data['file'] . ',');

    // Populate stasco_download_copies.
    $copies = $links->find('[stasco_download_copies]');
    self::editDownloadLinkShowOrHideDetail($copies, $data['copies']);
    $copies->html('Copies: ' . $data['copies'] . ',');

    // Populate stasco_download_retention.
    $retention = $links->find('[stasco_download_retention]');
    self::editDownloadLinkShowOrHideDetail($retention, $data['retention']);
    $retention->html('Retention: ' . $data['retention'] . ',');

    // Populate stasco_download_version.
    $version = $links->find('[stasco_download_version]');
    self::editDownloadLinkShowOrHideDetail($version, $data['version']);
    $version->html('Version: ' . $data['version']);
  }

  /**
   * Edit download link add a class based upon available $data.
   *
   * @param \phpQueryObject $link
   *   The link.
   * @param mixed $data
   *   The data.
   */
  private static function editDownloadLinkShowOrHideDetail(\phpQueryObject $link, $data) {
    if (!$data) {
      $class = $link->attr('class');
      if (!strpos($class, '--hidden')) {
        $class .= '--hidden';
        $link->attr('class', $class);
      }
    }
    $class = $link->attr('class');
    if (strpos($class, '--hidden')) {
      $class = substr($class, 0, strlen($class) - 8);
      $link->attr('class', $class);
    }
  }

  /**
   * Edit simple link.
   *
   * @param \phpQueryObject $links
   *   Links.
   * @param mixed $data
   *   The data.
   */
  protected static function editSimpleLinks(\phpQueryObject $links, $data) {
    $links->html($data['filename']);
  }

  /**
   * Update node replacing process.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node.
   * @param string $old_fid
   *   Old file id.
   * @param string $new_fid
   *   New file id.
   * @param string $data
   *   The data.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function updateNodeReplacingProcess(Node $node, $old_fid, $new_fid, $data) {
    // Register new file in usages.
    static::registerUploadedFile($node, $new_fid);

    // Replace new file info for existing link.
    $save_node = FALSE;
    $body = $node->get('body')->value;
    $doc = \phpQuery::newDocument($body);
    $links = pq('a[stasco_fid=' . $old_fid . ']');
    $links->attr('stasco_fid', $new_fid);
    $links->attr('href', $data['href']);
    $markups = pq('a[stasco_fid=' . $new_fid . '][stasco_markup]');
    if ($markups->elements) {
      $save_node = TRUE;
      static::editDownloadLinks($markups, $data);
    }
    $simple_links = pq('a[stasco_fid=' . $new_fid . '][stasco_simple_link]');
    if ($simple_links->elements) {
      $save_node = TRUE;
      static::editSimpleLinks($simple_links, $data);
    }
    if ($save_node) {
      // Update node's body.
      $updated_body = $doc->html();
      $node->body->value = $updated_body;
      $node->body->format = 'full_html';
      $node->save();
    }
  }

  /**
   * Registers uploaded file.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   * @param string $fid
   *   The file id.
   */
  protected static function registerUploadedFile(NodeInterface $node, $fid) {
    /** @var \Drupal\file\FileInterface $file */
    $file = \Drupal::entityTypeManager()->getStorage('file')->load($fid);
    $list = \Drupal::service('file.usage')->listUsage($file);
    if (!isset($list['stasco_ckeditor_plugins']['node'][$node->id()])) {
      \Drupal::service('file.usage')
        ->add($file, 'stasco_ckeditor_plugins', $node->getEntityTypeId(), $node->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateFileUsages($node) {
    $body = $node->get('body')->value;
    \phpQuery::newDocument($body);
    $files = pq('a[stasco_fid]');
    $files_in_body = [];
    if ($files->elements) {
      /** @var \DOMElement $element */
      foreach ($files->elements as $element) {
        $fid = $element->getAttribute('stasco_fid');
        $files_in_body[] = $fid;

        // Register usages of files links.
        /** @var \Drupal\file\FileInterface $file */
        $file = $this->entityTypeManager->getStorage('file')->load($fid);
        if (!$file) {
          continue;
        }
        $list = $this->fileUsage->listUsage($file);
        if (!isset($list['stasco_ckeditor_plugins']['node'][$node->id()])) {
          $this->fileUsage->add($file, 'stasco_ckeditor_plugins', $node->getEntityTypeId(), $node->id());
        }
      }
    }
    $query = $this->database->select('file_usage', 'fu');
    $query->condition('fu.module', 'stasco_ckeditor_plugins');
    $query->condition('fu.type', 'node');
    $query->condition('fu.id', $node->id());
    $query->addField('fu', 'fid', 'fid');
    $result = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    $files_in_node = [];
    foreach ($result as $element) {
      $files_in_node[] = $element['fid'];
    }
    $diff = array_diff($files_in_node, $files_in_body);
    if ($diff) {
      foreach ($diff as $fid) {
        /** @var \Drupal\file\FileInterface $file */
        $file = $this->entityTypeManager->getStorage('file')->load($fid);
        $this->fileUsage->delete($file, 'stasco_ckeditor_plugins', $node->getEntityTypeId(), $node->id());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteFileUsages($node) {
    $query = $this->database->select('file_usage', 'fu');
    $query->condition('fu.module', 'stasco_ckeditor_plugins');
    $query->condition('fu.type', 'node');
    $query->condition('fu.id', $node->id());
    $query->addField('fu', 'fid', 'fid');
    $result = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    foreach ($result as $element) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->entityTypeManager->getStorage('file')
        ->load($element['fid']);
      $this->fileUsage->delete($file, 'stasco_ckeditor_plugins', $node->getEntityTypeId(), $node->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function editUploadedFile($data) {
    $this->updateUploadedFileRecord($data);

    // Use default filename if file has not custom name.
    if (!$data['filename']) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->entityTypeManager->getStorage('file')->load($data['fid']);
      $data['filename'] = $file->getFilename();
    }

    $nodes = $this->getNodes($data['fid']);
    if ($nodes) {
      $batch_data = ['nodes' => $nodes, 'data' => $data];
      $this->batchManager->updateNodesBatchOperation($batch_data, 'edit');
    }
    else {
      $this->messenger->addMessage($this->t('We cant find usages for this file.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateUploadedFileRecord($data) {
    $connection = $this->database;
    if ($connection->schema()->tableExists('stasco_upload_file')) {
      $updated_data = [
        'fid' => $data['fid'],
        'filename' => $data['filename'],
        'file' => $data['file'],
        'copies' => $data['copies'],
        'retention' => $data['retention'],
        'version' => $data['version'],
      ];
      $connection->update('stasco_upload_file')
        ->condition('fid', $data['fid'])
        ->fields($updated_data)
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getNodes($fid) {
    $nodes = [];
    /** @var \Drupal\file\FileInterface $file */
    $file = $this->entityTypeManager->getStorage('file')->load($fid);
    $list = $this->fileUsage->listUsage($file);
    if (!empty($list['stasco_ckeditor_plugins']['node'])) {
      $ids = array_keys($list['stasco_ckeditor_plugins']['node']);
      if ($ids) {
        $nodes = $this->entityTypeManager->getStorage('node')
          ->loadMultiple($ids);
      }
    }
    return $nodes;
  }

  /**
   * {@inheritdoc}
   */
  public function replaceUploadedFile($old_fid, $new_fid, $data) {
    $nodes = $this->getNodes($old_fid);

    // Delete old file from usages.
    $this->deleteUploadedFile($old_fid);

    // Add record to stasco_upload_file.
    $this->addUploadedFileRecord($data);
    /** @var \Drupal\file\Entity\File $file */
    $file = $this->entityTypeManager->getStorage('file')->load($new_fid);
    $file->setPermanent();
    $file->save();

    // Use default filename if file has not custom name.
    if (!$data['filename']) {
      $data['filename'] = $file->getFilename();
    }

    if ($nodes) {
      $href = $this->fileUrlGenerator->transformRelative($this->fileUrlGenerator->generateAbsoluteString($file->getFileUri()));
      $data['href'] = $href;
      $batch_data = [
        'nodes' => $nodes,
        'old_fid' => $old_fid,
        'new_fid' => $new_fid,
        'data' => $data,
      ];
      $this->batchManager->updateNodesBatchOperation($batch_data, 'replace');
    }
    else {
      $this->messenger->addMessage($this->t('We have replaced this file, but could not find any usages for this file.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteUploadedFile($fid) {
    /** @var \Drupal\file\Entity\File $file */
    $file = $this->entityTypeManager->getStorage('file')->load($fid);
    $this->fileUsage->delete($file, 'stasco_ckeditor_plugins');
    $this->deleteUploadedFileRecord($fid);

    // Physically delete file.
    $file->delete();
    $this->fileSystem->delete($file->getFileUri());
  }

  /**
   * {@inheritdoc}
   */
  public function deleteUploadedFileRecord($fid) {
    $connection = $this->database;
    if ($connection->schema()->tableExists('stasco_upload_file')) {
      $connection->delete('stasco_upload_file')
        ->condition('fid', $fid)
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addUploadedFileRecord($data) {
    $connection = $this->database;
    if ($connection->schema()->tableExists('stasco_upload_file')) {
      $query = $connection->select('stasco_upload_file', 'suf');
      $query->condition('suf.fid', $data['fid']);
      $query->addField('suf', 'id', 'id');
      $result = $query->execute()->fetchCol();
      if (!$result) {
        $insert_data = [
          'fid' => $data['fid'],
          'filename' => $data['filename'],
          'file' => $data['file'],
          'copies' => $data['copies'],
          'retention' => $data['retention'],
          'version' => $data['version'],
        ];
        $connection->insert('stasco_upload_file')
          ->fields($insert_data)
          ->execute();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateNodeChangeFileDestination($node, $fid, $data) {
    $save_node = FALSE;
    $body = $node->get('body')->value;
    /** @var \Drupal\file\FileInterface $file */
    $file = $this->entityTypeManager->getStorage('file')->load($fid);
    $doc = \phpQuery::newDocument($body);
    $markups = pq('a[stasco_fid=' . $fid . '][stasco_markup]');
    if ($markups->elements) {
      $save_node = TRUE;
      $markups->attr('href', $data['href']);
      if (!$data['filename']) {
        $filename = $markups->find('[stasco_download_filename]');
        $filename->html($file->getFilename());
      }
    }
    $simple_links = pq('a[stasco_fid=' . $fid . '][stasco_simple_link]');
    if ($simple_links->elements) {
      $save_node = TRUE;
      $simple_links->attr('href', $data['href']);
      if (!$data['filename']) {
        $simple_links->html($file->getFilename());
      }
    }

    if ($save_node) {
      // Update node's body.
      $updated_body = $doc->html();
      $node->body->value = $updated_body;
      $node->body->format = 'full_html';
      $node->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getStascoFiles() {
    $files = [];
    if ($this->database->schema()->tableExists('stasco_upload_file')) {
      $query = $this->database->select('stasco_upload_file', 'suf');
      $query->fields('suf', ['fid']);
      $result = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
      if ($result) {
        foreach ($result as $record) {
          $files[] = $record['fid'];
        }
      }
    }

    return $files;
  }

  /**
   * {@inheritdoc}
   */
  public function isUsingStascoFile($fid) {
    $file_info = $this->getUploadedFileInfo($fid);
    if ($file_info) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->fileStorage->load($fid);
      $list = $this->fileUsage->listUsage($file);
      if (!empty($list['stasco_ckeditor_plugins']['node'])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getUploadedFileInfo($fid) {
    $result = [];
    if ($this->database->schema()->tableExists('stasco_upload_file')) {
      $query = $this->database->select('stasco_upload_file', 'suf');
      $query->condition('suf.fid', $fid);
      $query->fields('suf', [
        'filename',
        'file',
        'copies',
        'retention',
        'version',
      ]);
      $result = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
      if ($result) {
        return reset($result);
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkByUri($uri) {
    return substr_replace($uri, '/sites/default/files/', 0, strlen('public://'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFileByLink($href) {
    $uri = urldecode($this->getUriByLink($href));
    $files = $this->fileStorage->loadByProperties([
      'uri' => $uri,
      'status' => 1,
    ]);
    $file = reset($files);

    return $file;
  }

  /**
   * {@inheritdoc}
   */
  public function getUriByLink($href) {
    return substr_replace($href, 'public://', 0, strlen('/sites/default/files/'));
  }

}
