<?php

namespace Drupal\stasco_ckeditor_plugins\Form\Admin;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for editing of uploaded files.
 */
class EditUploadedFileForm extends UploadedFileBaseForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit_uploaded_file_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $fid = NULL) {
    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'stasco_ckeditor_plugins/stasco_ckeditor_plugins.edituploadedfile';
    $form_state->set('fid', $fid);
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
    /** @var \Drupal\file\Entity\File $file */
    $file = $this->fileStorage->load($fid);
    $href = $this->fileUrlGenerator->transformRelative($this->fileUrlGenerator->generateAbsoluteString($file->getFileUri()));
    $form['href'] = [
      '#title' => $this->t('Upload new file'),
      '#maxlength' => 1024,
      '#type' => 'textfield',
      '#default_value' => $href,
      '#attached' => ['library' => ['imce/drupal.imce.input']],
      '#attributes' => ['class' => ['imce-url-input']],
      '#ajax' => [
        'callback' => '::loadFile',
        'event' => 'change',
      ],
      '#weight' => -3,
    ];
    $form['file_info'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'stasco_file_info'],
    ];
    $form['file_info']['fid'] = [
      '#type' => 'value',
      '#value' => $fid,
    ];
    $form['file_info']['filename'] = [
      '#title' => $this->t('File name'),
      '#type' => 'textfield',
      '#default_value' => isset($file_info['filename']) ? $file_info['filename'] : '',
    ];
    $form['file_info']['file'] = [
      '#title' => $this->t('File'),
      '#type' => 'textfield',
      '#default_value' => isset($file_info['file']) ? $file_info['file'] : '',
    ];
    $form['file_info']['copies'] = [
      '#title' => $this->t('Copies'),
      '#type' => 'textfield',
      '#default_value' => isset($file_info['copies']) ? $file_info['copies'] : '',
    ];
    $form['file_info']['retention'] = [
      '#title' => $this->t('Retention'),
      '#type' => 'textfield',
      '#default_value' => isset($file_info['retention']) ? $file_info['retention'] : '',
    ];
    $form['file_info']['version'] = [
      '#title' => $this->t('Version'),
      '#type' => 'textfield',
      '#default_value' => isset($file_info['version']) ? $file_info['version'] : '',
    ];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Save'),
      '#name' => 'save',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $href = $values['href'];
    if (!$this->uploadFileManager->getFileByLink($href)) {
      $form_state->setError($form['href'], $this->t("Wrong link. Please, choose a file."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $fid = $form_state->get('fid');
    if ($button['#name'] == 'save') {
      $href = $form_state->getValue('href');
      $new_fid = $this->uploadFileManager->getFileByLink($href)->id();
      $file_info = $form_state->getValue($form['file_info']['#parents']);
      if ($fid == $new_fid) {
        if ($this->uploadFileManager->getUploadedFileInfo($fid)) {
          $this->uploadFileManager->editUploadedFile($file_info);
        }
        else {
          $this->uploadFileManager->addUploadedFileRecord($file_info);
        }
      }
      elseif ($new_fid) {
        $file_info['fid'] = $new_fid;
        $this->uploadFileManager->replaceUploadedFile($fid, $new_fid, $file_info);
      }
    }
    $form_state->setRedirect('stasco_ckeditor_plugins.stasco_uploaded_files');
  }

  /**
   * {@inheritdoc}
   */
  public function loadFile(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $values = $form_state->getValues();
    $href = $values['href'];
    if ($href && $this->uploadFileManager->getFileByLink($href)) {
      $values = $this->prepareFileData($values, $href);
      if ($file_info = $this->uploadFileManager->getUploadedFileInfo($values['file_info']['fid'])) {
        $form['file_info']['filename']['#value'] = $file_info['filename'];
        $form['file_info']['file']['#value'] = $file_info['file'];
        $form['file_info']['copies']['#value'] = $file_info['copies'];
        $form['file_info']['retention']['#value'] = $file_info['retention'];
        $form['file_info']['version']['#value'] = $file_info['version'];
        if ($values['file_info']['fid'] != $form_state->get('fid')) {
          $form['file_info']['filename']['#attributes']['disabled'] = 'disabled';
          $form['file_info']['file']['#attributes']['disabled'] = 'disabled';
          $form['file_info']['copies']['#attributes']['disabled'] = 'disabled';
          $form['file_info']['retention']['#attributes']['disabled'] = 'disabled';
          $form['file_info']['version']['#attributes']['disabled'] = 'disabled';
        }
      }
      else {
        $form['file_info']['filename']['#value'] = '';
        if (isset($form['file_info']['filename']['#attributes']['disabled'])) {
          unset($form['file_info']['filename']['#attributes']['disabled']);
        }
        $form['file_info']['file']['#value'] = '';
        if (isset($form['file_info']['file']['#attributes']['disabled'])) {
          unset($form['file_info']['file']['#attributes']['disabled']);
        }
        $form['file_info']['copies']['#value'] = '';
        if (isset($form['file_info']['copies']['#attributes']['disabled'])) {
          unset($form['file_info']['copies']['#attributes']['disabled']);
        }
        $form['file_info']['retention']['#value'] = '';
        if (isset($form['file_info']['retention']['#attributes']['disabled'])) {
          unset($form['file_info']['retention']['#attributes']['disabled']);
        }
        $form['file_info']['version']['#value'] = '';
        if (isset($form['file_info']['version']['#attributes']['disabled'])) {
          unset($form['file_info']['version']['#attributes']['disabled']);
        }
      }
      $selector = '#stasco_file_info';
      $response->addCommand(new ReplaceCommand($selector, $form['file_info']));
    }

    return $response;
  }

  /**
   * Prepares file data.
   *
   * @param array $values
   *   File id.
   * @param string $href
   *   Link to file.
   *
   * @return array
   *   List of ids.
   */
  protected function prepareFileData(array &$values, $href) {
    $file = $this->uploadFileManager->getFileByLink($href);
    $values['file_info']['fid'] = $file->id();

    return $values;
  }

}
