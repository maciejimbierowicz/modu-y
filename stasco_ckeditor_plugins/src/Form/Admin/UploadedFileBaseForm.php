<?php

namespace Drupal\stasco_ckeditor_plugins\Form\Admin;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormBase;
use Drupal\file\FileUsage\DatabaseFileUsageBackend;
use Drupal\stasco_ckeditor_plugins\UploadFileManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Uploaded file base form.
 */
abstract class UploadedFileBaseForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * File usage.
   *
   * @var \Drupal\file\FileUsage\DatabaseFileUsageBackend
   */
  protected $fileUsage;

  /**
   * Uplaod file manager.
   *
   * @var \Drupal\stasco_ckeditor_plugins\UploadFileManager
   */
  protected $uploadFileManager;

  /**
   * The file url generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * File storage interface.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * Constructs uploaded file base form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\file\FileUsage\DatabaseFileUsageBackend $file_usage
   *   File usage.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file url generator.
   * @param \Drupal\stasco_ckeditor_plugins\UploadFileManager $upload_file_manager
   *   The upload file manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, DatabaseFileUsageBackend $file_usage, FileUrlGeneratorInterface $file_url_generator, UploadFileManager $upload_file_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUsage = $file_usage;
    $this->fileUrlGenerator = $file_url_generator;
    $this->uploadFileManager = $upload_file_manager;
    try {
      $this->fileStorage = $this->entityTypeManager->getStorage('file');
    }
    catch (InvalidPluginDefinitionException $exception) {
      $this->messenger()->addMessage("Can't load file storage.", 'error');
    }
  }

  /**
   * Creates services.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   *
   * @return static
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file.usage'),
      $container->get('file_url_generator'),
      $container->get('stasco_ckeditor_plugins.upload_file_manager')
    );
  }

  /**
   * Get file info markup.
   *
   * @param mixed $fid
   *   File ids.
   *
   * @return array
   *   Files array.
   */
  protected function getFileInfoMarkup($fid) {
    $file_info = $this->uploadFileManager->getUploadedFileInfo($fid);
    if ($file_info) {
      /** @var \Drupal\file\Entity\File $file */
      $file = $this->fileStorage->load($fid);
      $href = $this->fileUrlGenerator->transformRelative($this->fileUrlGenerator->generateAbsoluteString($file->getFileUri()));
      $file_info['href'] = $href;
      $markup = [
        '#theme' => 'uploaded_file_info',
        '#data' => $file_info,
        '#weight' => -1,
      ];
    }
    else {
      $markup = [
        '#theme' => 'file_managing_error',
        '#message' => $this->getNotStascoUploadedFileOrDeletedMessage(),
        '#prefix' => '<div id="uploaded-file-ajax-form">',
        '#suffix' => '</div>',
      ];
    }

    return $markup;
  }

  /**
   * File uploaded not by module.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Message if unknown file.
   */
  protected function getNotStascoUploadedFileOrDeletedMessage() {
    return $this->t('This file was not added through stasco upload file plugin or was deleted and cannot be processed.');
  }

}
