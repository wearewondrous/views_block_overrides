<?php

namespace Drupal\views_block_overrides\Plugin\views\row;

use Drupal\views\Plugin\views\row\EntityRow;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\views\Entity\Render\EntityTranslationRenderTrait;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generic entity row plugin to provide a common base for all entity types.
 *
 * @ViewsRow(
 *   id = "entity_row_with_dynamic_format",
 *   deriver = "Drupal\views_block_overrides\Plugin\Derivative\ViewsEntityRowDynamicFormat"
 * )
 */
class EntityRowWithDynamicFormat extends EntityRow {

  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    if ($view_mode_override = $this->getViewModeOverride($view, $display, $options)) {
      $options['view_mode'] = $view_mode_override;
    }
    parent::init($view, $display, $options);
  }

  /**
   * Checks for view mode overrides.
   */
  public function getViewModeOverride(ViewExecutable $view, DisplayPluginBase $display, array $options) {
    if (!isset($view->views_block_overrides['block_instance'])) {
      return $options['view_mode'];
    }
    $configuration = $view->views_block_overrides['block_instance']->getConfiguration();
    if (!isset($configuration['dynamic_format']['format']) || $configuration['dynamic_format']['format'] == 'default') {
      return $options['view_mode'];
    }
    return $configuration['dynamic_format']['format'];
  }

  /**
   * Get's the possible format options.
   *
   * @return array
   */
  public function getFormatOptions() {
    return \Drupal::entityTypeManager()->getViewModeOptions($this->entityTypeId);
  }
}
