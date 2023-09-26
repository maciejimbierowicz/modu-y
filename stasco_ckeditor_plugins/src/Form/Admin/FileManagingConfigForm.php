<?php

namespace Drupal\stasco_ckeditor_plugins\Form\Admin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\stasco\Form\Admin\ConfigForm;

/**
 * Form for configuration of file manager.
 */
class FileManagingConfigForm extends ConfigForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'file_managing_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('stasco_file_managing.settings');
    // Batch settings.
    $form['batch_settings_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Batch settings'),
    ];
    $form['batch_settings_fieldset']['node_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Node limit'),
      '#default_value' => $config->get('batch_node_limit') ?: 25,
      '#min' => 1,
      '#description' => $this->t('Number of nodes to process in one iteration of the batch.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

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
    $config = $this->configFactory->getEditable('stasco_file_managing.settings');
    $config->set('batch_node_limit', $form_state->getValue($form['batch_settings_fieldset']['node_limit']['#parents']));
    $config->save();
  }

}
