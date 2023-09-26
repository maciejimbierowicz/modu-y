<?php

namespace Drupal\stasco_ckeditor_plugins\Controller;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\linkit\SuggestionManager;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\linkit\Controller\AutocompleteController;

/**
 * The custom autocomplete controler.
 */
class CustomAutocompleteController extends AutocompleteController {

  /**
   * The alias manager interface.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  private $aliasManager;

  /**
   * Constructs custom autocomplete controller.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $linkit_profile_storage
   *   The linkit profile storage service.
   * @param \Drupal\linkit\SuggestionManager $suggestionManager
   *   The suggestion service.
   * @param \Drupal\path_alias\AliasManagerInterface $aliasManager
   *   The path alias manager.
   */
  public function __construct(EntityStorageInterface $linkit_profile_storage, SuggestionManager $suggestionManager, AliasManagerInterface $aliasManager) {
    parent::__construct($linkit_profile_storage, $suggestionManager);
    $this->aliasManager = $aliasManager;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\stasco_ckeditor_plugins\Controller\CustomAutocompleteController|static
   *   The custom autocomlite controller.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('linkit_profile'),
      $container->get('linkit.suggestion_manager'),
      $container->get('path_alias.manager')
    );
  }

  /**
   * Menu callback for linkit search autocompletion.
   *
   * Like other autocomplete functions, this function inspects the 'q' query
   * parameter for the string to use to search for suggestions.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $linkit_profile_id
   *   The linkit profile id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function autocomplete(Request $request, $linkit_profile_id) {
    $this->linkitProfile = $this->linkitProfileStorage->load($linkit_profile_id);
    $string = mb_strtolower($request->query->get('q'));

    $matches = $this->suggestionManager->getSuggestions($this->linkitProfile, mb_strtolower($string));

    foreach ($matches->getSuggestions() as $value) {
      $path = $this->aliasManager->getPathByAlias($value->getPath());
      if (preg_match('/node\/(\d+)/', $path, $preg_matches)) {
        $value->setPath($path);
      }
    }

    return new JsonResponse($matches);
  }

}
