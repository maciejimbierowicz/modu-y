<?php

namespace Drupal\stasco_ckeditor_plugins\Form\Admin;

use Drupal\Core\Form\FormStateInterface;

/**
 * The delete uploaded files form.
 */
class DeleteUploadedFileForm extends UploadedFileBaseForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'delete_uploaded_file_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $fid = NULL) {
    $form['#tree'] = TRUE;
    $form['fid'] = [
      '#type' => 'value',
      '#value' => $fid,
    ];
    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => 10,
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#name' => 'cancel',
      '#weight' => -1,
    ];
    $file_info = $this->uploadFileManager->getUploadedFileInfo($fid);
    if ($file_info) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->fileStorage->load($fid);
      $list = $this->fileUsage->listUsage($file);
      if (!empty($list['stasco_ckeditor_plugins']['node'])) {
        $form['error'] = [
          '#theme' => 'file_managing_error',
          '#message' => $this->t('This file is being used in other pages, please remove it from these pages before deleting!'),
          '#weight' => -1,
        ];
      }
      else {
        $form['status'] = [
          '#theme' => 'file_managing_status',
          '#message' => $this->t('This file is not used in any other pages, you can safely delete it.'),
          '#weight' => -1,
        ];
      }
      $form['file_info'] = $this->getFileInfoMarkup($fid);
      if (!empty($list['stasco_ckeditor_plugins']['node'])) {
        $form['list_of_nodes'] = [
          '#theme' => 'stasco_list_of_nodes',
          '#nodes' => $list['stasco_ckeditor_plugins']['node'],
        ];
      }
      else {
        $form['actions']['Delete'] = [
          '#type' => 'submit',
          '#button_type' => 'primary',
          '#value' => $this->t('Delete'),
          '#name' => 'delete',
        ];
      }
    }
    else {
      $form['error'] = [
        '#theme' => 'file_managing_error',
        '#message' => $this->getNotStascoUploadedFileOrDeletedMessage(),
        '#weight' => -1,
      ];
      $form['file_info'] = NULL;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $fid = $form_state->getValue($form['fid']['#parents']);
    if ($button['#name'] == 'delete') {
      $this->uploadFileManager->deleteUploadedFile($fid);
    }
    $form_state->setRedirect('stasco_ckeditor_plugins.stasco_uploaded_files');
  }

}
