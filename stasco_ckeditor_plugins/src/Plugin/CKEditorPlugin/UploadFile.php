<?php

namespace Drupal\stasco_ckeditor_plugins\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\editor\Entity\Editor;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the "uploadfile" plugin.
 *
 * @CKEditorPlugin(
 *   id = "uploadfile",
 *   label = @Translation("Upload file"),
 *   module = "stasco_ckeditor_plugins"
 * )
 */
class UploadFile extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface, ContainerFactoryPluginInterface {

  /**
   * The Linkit profile storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $linkitProfileStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $linkit_profile_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->linkitProfileStorage = $linkit_profile_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('linkit_profile')
    );
  }

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::isInternal().
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return $this->getModulePath('stasco_ckeditor_plugins') . '/js/plugins/uploadfile/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [
      'uploadfile_dialogTitleAdd' => $this->t('Add file link'),
      'uploadfile_dialogTitleEdit' => $this->t('Edit file link'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'UploadFile' => [
        'label' => $this->t('Upload file'),
        'image' => $this->getModulePath('stasco_ckeditor_plugins') . '/js/plugins/uploadfile/icons/uploadfile.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $settings = $editor->getSettings();

    $all_profiles = $this->linkitProfileStorage->loadMultiple();

    $options = [];
    foreach ($all_profiles as $profile) {
      $options[$profile->id()] = $profile->label();
    }

    $form['linkit_profile'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a linkit profile'),
      '#options' => $options,
      '#default_value' => isset($settings['plugins']['uploadfile']) ? $settings['plugins']['uploadfile'] : '',
      '#empty_option' => $this->t('- Select profile -'),
      '#description' => $this->t('Select the linkit profile you wish to use with this text format.'),
      '#element_validate' => [
        [$this, 'validateLinkitProfileSelection'],
      ],
    ];

    return $form;
  }

  /**
   * Validates linkit profile selection.
   *
   * #element_validate handler for the "linkit_profile" element in
   * settingsForm().
   */
  public function validateLinkitProfileSelection(array $element, FormStateInterface $form_state) {
    $toolbar_buttons = $form_state->getValue([
      'editor',
      'settings',
      'toolbar',
      'button_groups',
    ]);
    if (strpos($toolbar_buttons, '"UploadFile"') !== FALSE && empty($element['#value'])) {
      $form_state->setError($element, $this->t('Please select the linkit profile you wish to use.'));
    }
  }

}
