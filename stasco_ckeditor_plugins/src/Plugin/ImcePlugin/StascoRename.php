<?php

namespace Drupal\stasco_ckeditor_plugins\Plugin\ImcePlugin;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\file\FileRepositoryInterface;
use Drupal\imce\Imce;
use Drupal\imce\ImceFM;
use Drupal\imce_rename_plugin\Plugin\ImcePlugin\Rename;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines Stasco Imce Rename plugin.
 *
 * @ImcePlugin(
 *   id = "rename",
 *   label = "Rename",
 *   weight = -5,
 *   operations = {
 *     "rename" = "opRename"
 *   }
 * )
 */
class StascoRename extends Rename {


  /**
   * The file system interface.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $file_url_generator;

  /**
   * The file repository service.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected $file_repository;

  /**
   * Constructs Stasco Rename.
   *
   * @param array $configuration
   *   The comfiguration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger.
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   * @param \Drupal\file\FileRepositoryInterface $file_repository
   *   The file repository.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration helper.
   * @param \Drupal\Core\File\FileSystemInterface $filesystem
   *   The file system.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   * @param \Drupal\file\FileRepositoryInterface $file_repository
   *   The file repository service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Messenger $messenger, Connection $database, FileRepositoryInterface $file_repository, TransliterationInterface $transliteration, FileSystemInterface $filesystem, FileUrlGeneratorInterface $file_url_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $messenger, $database, $file_repository, $transliteration);
    $this->fileSystem = $filesystem;
    $this->file_url_generator = $file_url_generator;
    $this->file_repository = $file_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('messenger'),
      $container->get('database'),
      $container->get('file.repository'),
      $container->get('transliteration'),
      $container->get('file_system'),
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildPage(array &$page, ImceFM $fm) {
    $check_perm = $fm->hasPermission('rename_files') || $fm->hasPermission('rename_folders');
    // Check if rename permission exists.
    if ($check_perm) {
      $page['#attached']['library'][] = 'stasco_ckeditor_plugins/stasco_ckeditor_plugins.imce.rename';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function renameFile(ImceFM $fm, string $old_name) {
    $new_name = $this->getNewName($fm);
    $folder = $fm->activeFolder;
    $uri = $folder->getUri();
    // Add extension to file name.
    $new_name = $new_name . '.' . pathinfo($old_name, PATHINFO_EXTENSION);
    $new_uri = $uri == 'public://' ? $uri . $new_name : $uri . '/' . $new_name;
    $old_uri = $uri == 'public://' ? $uri . $old_name : $uri . '/' . $old_name;

    if (file_exists($new_uri)) {
      $this->messenger->addMessage($this->t('Failed to rename file because "@old_item" already exists', [
        '@old_item' => utf8_encode($old_name),
      ]), 'error');
      return;
    }

    // Check access to write file and try to change chmod.
    if (!is_writable($old_uri) && !chmod($old_uri, 0664)) {
      $this->messenger->addMessage($this->t('No permissions to write file "@old_item". Please upload the file via IMCE.', [
        '@old_item' => utf8_encode($old_name),
      ]), 'error');
      return;
    }

    $file = Imce::getFileEntity($old_uri);
    // Create entity when there is no entity for the file.
    $file = empty($file) ? Imce::createFileEntity($old_uri) : $file;
    $move = $this->file_repository->move($file, $new_uri, FileSystemInterface::EXISTS_ERROR);
    $move->setFilename($new_name);
    $move->save();

    /** @var \Drupal\stasco_ckeditor_plugins\UploadFileManager $upload_file_manager */
    $upload_file_manager = \Drupal::service('stasco_ckeditor_plugins.upload_file_manager');
    $file_info = $upload_file_manager->getUploadedFileInfo($move->id());
    if ($file_info) {
      $href = $this->file_url_generator->generateAbsoluteString($new_uri);

      $file_info['href'] = $this->file_url_generator->transformRelative($href);
      $nodes = $upload_file_manager->getNodes($file->id());
      foreach ($nodes as $node) {
        $upload_file_manager->updateNodeChangeFileDestination($node, $move->id(), $file_info);
      }
    }

    // Validate message.
    if ($move) {
      $this->messenger->addMessage($this->t('Rename successful! Renamed "@old_item" to "@new_item"', [
        '@old_item' => utf8_encode($old_name),
        '@new_item' => utf8_encode($new_name),
      ]));
      $folder->addFile($new_name)->addToJs();
      $folder->getItem($old_name)->removeFromJs();
    }
    else {
      $this->messenger->addMessage($this->t('Failed to rename file "@old_item" to "@new_item".', [
        '@old_item' => utf8_encode($old_name),
        '@new_item' => utf8_encode($new_name),
      ]), 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getNewName(ImceFM $fm) {
    $name = $fm->getPost('new_name');
    // Transliteration name.
    $name = $this->transliteration->transliterate($name);
    // Set timestamp when name empty.
    $name = empty($name) ? time() : $name;

    return $name;
  }

  /**
   * {@inheritdoc}
   */
  public function renameFolder(ImceFM $fm, string $old_name) {
    $new_name = $this->getNewName($fm);
    $folder = $fm->activeFolder;
    $uri = $folder->getUri();
    $new_uri = $uri . '/' . $new_name;
    $old_uri = $uri . '/' . $old_name;

    $files = $this->fileSystem->scanDirectory($old_uri, '/.*/');
    if ($files) {
      $this->messenger->addMessage($this->t('You cannot change the name of this folder as it already contains files. You must delete these files first before renaming the folder'), 'error');
      return;
    }

    if (file_exists($new_uri)) {
      $this->messenger->addMessage($this->t('Failed to rename folder because "@old_item" already exists', [
        '@old_item' => utf8_encode($old_name),
      ]), 'error');
      return;
    }

    if (rename($old_uri, $new_uri)) {
      $this->messenger->addMessage($this->t('Rename successful! Renamed "@old_item" to "@new_item"', [
        '@old_item' => utf8_encode($old_name),
        '@new_item' => utf8_encode($new_name),
      ]));
      $folder->addSubfolder($new_name)->addToJs();
      $folder->getItem($old_name)->removeFromJs();

    }
    else {
      $this->messenger->addMessage($this->t('Sorry, but something went wrong when renaming a folder'), 'error');
    }
  }

}
