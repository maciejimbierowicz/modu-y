<?php

namespace Drupal\stasco_ckeditor_plugins\Plugin\Linkit\Matcher;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\linkit\Plugin\Linkit\Matcher\NodeMatcher;
use Drupal\linkit\SubstitutionManagerInterface;
use Drupal\linkit\Suggestion\SuggestionCollection;
use Drupal\stasco_chapters\ChaptersManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The stasco content matcher.
 *
 * @Matcher(
 *   id = "stasco_content",
 *   target_entity = "node",
 *   label = @Translation("Stasco Content"),
 *   provider = "node"
 * )
 */
class StascoContentMatcher extends NodeMatcher {

  /**
   * Chapter manager.
   *
   * @var \Drupal\stasco_chapters\ChaptersManagerInterface
   */
  protected $chaptersManager;

  /**
   * Constructs stasco chapters marcher.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   Te plugin definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\linkit\SubstitutionManagerInterface $substitution_manager
   *   The substitution manager.
   * @param \Drupal\stasco_chapters\ChaptersManagerInterface $chaptersManager
   *   The chapters manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityRepositoryInterface $entity_repository, ModuleHandlerInterface $module_handler, AccountInterface $current_user, SubstitutionManagerInterface $substitution_manager, ChaptersManagerInterface $chaptersManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $database, $entity_type_manager, $entity_type_bundle_info, $entity_repository, $module_handler, $current_user, $substitution_manager);

    $this->chaptersManager = $chaptersManager;
  }

  /**
   * Creates services.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   *
   * @return \Drupal\linkit\Plugin\Linkit\Matcher\EntityMatcher|\Drupal\stasco_ckeditor_plugins\Plugin\Linkit\Matcher\StascoContentMatcher|static
   *   The stasco content matcher.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity.repository'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('plugin.manager.linkit.substitution'),
      $container->get('stasco.chapters')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($string) {
    $suggestions = new SuggestionCollection();
    $query = $this->buildEntityQuery($string)->accessCheck();
    $query_result = $query->execute();
    $url_results = $this->findEntityIdByUrl($string);
    $result = array_merge($query_result, $url_results);

    // If no results, return an empty suggestion collection.
    if (empty($result)) {
      return $suggestions;
    }

    $entities = $this->entityTypeManager->getStorage($this->targetType)
      ->loadMultiple($result);

    foreach ($entities as $entity) {
      // Check the access against the defined entity access handler.
      /** @var \Drupal\Core\Access\AccessResultInterface $access */
      $access = $entity->access('view', $this->currentUser, TRUE);
      if (!$access->isAllowed()) {
        continue;
      }

      $entity = $this->entityRepository->getTranslationFromContext($entity);
      $suggestion = $this->createSuggestion($entity);
      $suggestion->setLabel($this->chaptersManager->getChapterTitleWithNumbering($entity->id(), $this->buildLabel($entity)));

      $suggestions->addSuggestion($suggestion);
    }

    return $suggestions;
  }

}
