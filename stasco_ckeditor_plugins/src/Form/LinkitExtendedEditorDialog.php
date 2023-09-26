<?php

namespace Drupal\stasco_ckeditor_plugins\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\filter\Entity\FilterFormat;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a linkit dialog for text editors.
 */
class LinkitExtendedEditorDialog extends FormBase {

  /**
   * The linkit profile.
   *
   * @var \Drupal\linkit\ProfileInterface
   */
  protected $linkitProfile;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs linkit extended dialog.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'linkitextended_editor_dialog_form';
  }

  /**
   * Builds form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param \Drupal\filter\Entity\FilterFormat|null $filter_format
   *   The filter format.
   *
   * @return array
   *   The form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function buildForm(array $form, FormStateInterface $form_state, FilterFormat $filter_format = NULL) {
    // The default values are set directly from \Drupal::request()->request,
    // provided by the editor plugin opening the dialog.
    $user_input = $form_state->getUserInput();
    $input = isset($user_input['editor_object']) ? $user_input['editor_object'] : [];

    $editor_storage = $this->entityTypeManager->getStorage('editor');
    $editor = $editor_storage->load($filter_format->id());
    $linkit_profile_id = $editor->getSettings()['plugins']['linkitextended']['linkit_profile'];
    $this->linkitProfile = $this->entityTypeManager->getStorage('linkit_profile')->load($linkit_profile_id);

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#prefix'] = '<div id="linkit-editor-dialog-form">';
    $form['#suffix'] = '</div>';
    $form['#attributes']['data-linkitextended-editor-dialog-form'] = 'cke-dialog-form';

    // Everything under the "attributes" key is merged directly into the
    // generated link tag's attributes.
    $form['attributes']['href'] = [
      '#title' => $this->t('Link'),
      '#type' => 'linkit',
      '#default_value' => isset($input['href']) ? $input['href'] : '',
      '#description' => $this->t('Start typing to find content or paste a URL.'),
      '#autocomplete_route_name' => 'stasco_ckeditor_plugins.autocomplete',
      '#autocomplete_route_parameters' => [
        'linkit_profile_id' => $linkit_profile_id,
      ],
      '#weight' => 0,
    ];
    $form['attributes']['linkextended'] = [
      '#type' => 'value',
      '#value' => TRUE,
    ];

    $form['autotitle'] = [
      '#type' => 'hidden',
    ];
    $form['autotitle']['#attached']['library'][] = 'stasco_ckeditor_plugins/stasco_ckeditor_plugins.autotitle';

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitForm',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($form_state->getErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#linkitextended-editor-dialog-form', $form));
    }
    else {
      $response->addCommand(new EditorDialogSave($form_state->getValues()));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

}
