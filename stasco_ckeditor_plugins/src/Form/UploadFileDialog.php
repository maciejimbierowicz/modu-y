<?php

namespace Drupal\stasco_ckeditor_plugins\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\stasco_ckeditor_plugins\UploadFileManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a upload file dialog for text editors.
 */
class UploadFileDialog extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * The file manager service.
   *
   * @var \Drupal\stasco_ckeditor_plugins\UploadFileManagerInterface
   */
  protected $uploadFileManager;

  /**
   * The file storage service.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * The editor storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $editorStorage;

  /**
   * The storage inteface.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $linkitProfileStorage;

  /**
   * Constructs a form object for linkit dialog.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $editor_storage
   *   The editor storage service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $linkit_profile_storage
   *   The linkit profile storage service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   * @param \Drupal\stasco_ckeditor_plugins\UploadFileManagerInterface $upload_file_manager
   *   The file upload manager.
   */
  public function __construct(EntityStorageInterface $editor_storage, EntityStorageInterface $linkit_profile_storage, EntityTypeManagerInterface $entity_type_manager, FileSystemInterface $file_system, UploadFileManagerInterface $upload_file_manager) {
    $this->editorStorage = $editor_storage;
    $this->linkitProfileStorage = $linkit_profile_storage;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->uploadFileManager = $upload_file_manager;
    try {
      $this->fileStorage = $this->entityTypeManager->getStorage('file');
    }
    catch (InvalidPluginDefinitionException $exception) {
      $this->messenger()->addMessage("Can't load file storage.", 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('editor'),
      $container->get('entity_type.manager')->getStorage('linkit_profile'),
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('stasco_ckeditor_plugins.upload_file_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uploadfile_editor_dialog_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FilterFormat $filter_format = NULL) {
    // The default values are set directly from \Drupal::request()->request,
    // provided by the editor plugin opening the dialog.
    $user_input = $form_state->getUserInput();
    $input = isset($user_input['editor_object']) ? $user_input['editor_object'] : [];

    $editor_storage = $this->editorStorage;
    $editor = $editor_storage->load($filter_format->id());
    $linkit_profile_id = $editor->getSettings()['plugins']['uploadfile']['linkit_profile'];
    $this->linkitProfile = $this->linkitProfileStorage->load($linkit_profile_id);

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#attached']['library'][] = 'stasco_ckeditor_plugins/stasco_ckeditor_plugins.uploadfile';
    $form['#prefix'] = '<div id="upload-file-editor-dialog-form">';
    $form['#suffix'] = '</div>';
    $form['#attributes']['data-uploadfile-editor-dialog-form'] = 'cke-dialog-form';

    // Upload section.
    $form['upload_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => 'Choose or upload new file',
    ];
    $form['upload_fieldset']['href'] = [
      '#title' => $this->t('File'),
      '#type' => 'textfield',
      '#maxlength' => 1024,
      '#required' => TRUE,
      '#attached' => ['library' => ['imce/drupal.imce.input']],
      '#attributes' => ['class' => ['imce-url-input']],
      '#default_value' => isset($input['href']) ? $input['href'] : '',
      '#description' => $this->t('Start typing to find file or paste a URL.'),
      '#ajax' => [
        'callback' => '::loadFile',
        'event' => 'change',
      ],
      '#weight' => 0,
    ];
    $form['upload_fieldset']['file_info'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'stasco_file_info'],
    ];
    $form['upload_fieldset']['file_info']['filename'] = [
      '#title' => $this->t('File name'),
      '#type' => 'textfield',
      '#default_value' => '',
    ];
    $form['upload_fieldset']['file_info']['file'] = [
      '#title' => $this->t('File'),
      '#type' => 'textfield',
      '#default_value' => '',
    ];
    $form['upload_fieldset']['file_info']['copies'] = [
      '#title' => $this->t('Copies'),
      '#type' => 'textfield',
      '#default_value' => '',
    ];
    $form['upload_fieldset']['file_info']['retention'] = [
      '#title' => $this->t('Retention'),
      '#type' => 'textfield',
      '#default_value' => '',
    ];
    $form['upload_fieldset']['file_info']['version'] = [
      '#title' => $this->t('Version'),
      '#type' => 'textfield',
      '#default_value' => '',
    ];

    // Here user can choose what type of link they want to add.
    $options['markup'] = $this->t('Full markup');
    $options['simple_link'] = $this->t('Simple link');
    $form['markup_or_simple_link'] = [
      '#type' => 'radios',
      '#title' => 'Choose what type of file link would you like to add.',
      '#options' => $options,
      '#default_value' => 'simple_link',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#ajax' => [
        'callback' => '::submitAjaxForm',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    if ($button['#type'] == 'submit') {
      $values = $form_state->getValues();
      $href = $values['upload_fieldset']['href'];
      if (!$this->uploadFileManager->getFileByLink($href)) {
        $form_state->setError($form['upload_fieldset']['href'], $this->t('Wrong link. Please, choose a file.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $href = $values['upload_fieldset']['href'];
    if ($href) {
      $values = $this->prepareFileData($values, $href);
      if (!$this->uploadFileManager->getUploadedFileInfo($values['upload_fieldset']['file_info']['fid'])) {
        $this->uploadFileManager->addUploadedFileRecord($values['upload_fieldset']['file_info']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitAjaxForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($form_state->getErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#upload-file-editor-dialog-form', $form));
    }
    else {
      $values = $form_state->getValues();
      $href = $values['upload_fieldset']['href'];
      if ($href) {
        $values = $this->prepareFileData($values, $href);
        $file = $this->uploadFileManager->getFileByLink($href);
        // @todo If file is exist that using file info instead user's inputted data.
        if ($file_info = $this->uploadFileManager->getUploadedFileInfo($values['upload_fieldset']['file_info']['fid'])) {
          $values['upload_fieldset']['file_info']['filename'] = $file_info['filename'] ?: $file->getFilename();
          $values['upload_fieldset']['file_info']['file'] = $file_info['file'];
          $values['upload_fieldset']['file_info']['copies'] = $file_info['copies'];
          $values['upload_fieldset']['file_info']['retention'] = $file_info['retention'];
          $values['upload_fieldset']['file_info']['version'] = $file_info['version'];
        }
        $response->addCommand(new EditorDialogSave($values));
        $response->addCommand(new CloseModalDialogCommand());
      }
    }

    return $response;
  }

  /**
   * Adds file data.
   *
   * @param array $values
   *   Form state values.
   * @param string $href
   *   The link.
   *
   * @return array
   *   Updated form tsate.
   */
  protected function prepareFileData(array &$values, $href) {
    $file = $this->uploadFileManager->getFileByLink($href);
    $values['upload_fieldset']['file_info']['fid'] = $file->id();

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function loadFile(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $values = $form_state->getValues();
    $href = $values['upload_fieldset']['href'];
    if ($href && $this->uploadFileManager->getFileByLink($href)) {
      $values = $this->prepareFileData($values, $href);
      if ($file_info = $this->uploadFileManager->getUploadedFileInfo($values['upload_fieldset']['file_info']['fid'])) {
        $form['upload_fieldset']['file_info']['filename']['#value'] = $file_info['filename'];
        $form['upload_fieldset']['file_info']['filename']['#attributes']['disabled'] = 'disabled';
        $form['upload_fieldset']['file_info']['file']['#value'] = $file_info['file'];
        $form['upload_fieldset']['file_info']['file']['#attributes']['disabled'] = 'disabled';
        $form['upload_fieldset']['file_info']['copies']['#value'] = $file_info['copies'];
        $form['upload_fieldset']['file_info']['copies']['#attributes']['disabled'] = 'disabled';
        $form['upload_fieldset']['file_info']['retention']['#value'] = $file_info['retention'];
        $form['upload_fieldset']['file_info']['retention']['#attributes']['disabled'] = 'disabled';
        $form['upload_fieldset']['file_info']['version']['#value'] = $file_info['version'];
        $form['upload_fieldset']['file_info']['version']['#attributes']['disabled'] = 'disabled';
      }
      else {
        $form['upload_fieldset']['file_info']['filename']['#value'] = '';
        unset($form['upload_fieldset']['file_info']['filename']['#attributes']['disabled']);
        $form['upload_fieldset']['file_info']['file']['#value'] = '';
        unset($form['upload_fieldset']['file_info']['file']['#attributes']['disabled']);
        $form['upload_fieldset']['file_info']['copies']['#value'] = '';
        unset($form['upload_fieldset']['file_info']['copies']['#attributes']['disabled']);
        $form['upload_fieldset']['file_info']['retention']['#value'] = '';
        unset($form['upload_fieldset']['file_info']['retention']['#attributes']['disabled']);
        $form['upload_fieldset']['file_info']['version']['#value'] = '';
        unset($form['upload_fieldset']['file_info']['version']['#attributes']['disabled']);
      }
      $selector = '#stasco_file_info';
      $response->addCommand(new ReplaceCommand($selector, $form['upload_fieldset']['file_info']));
    }

    return $response;
  }

}
