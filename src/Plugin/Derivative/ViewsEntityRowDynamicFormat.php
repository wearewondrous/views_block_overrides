<?php

namespace Drupal\views_block_overrides\Plugin\Derivative;

use Drupal\views\Plugin\Derivative\ViewsEntityRow;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\views\ViewsData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides views row plugin definitions for all non-special entity types.
 *
 * @ingroup views_row_plugins
 *
 * @see \Drupal\views\Plugin\views\row\EntityRow
 */
class ViewsEntityRowDynamicFormat extends ViewsEntityRow {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    parent::getDerivativeDefinitions($base_plugin_definition);
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      // Just add support for entity types which have a views integration.
      if (($base_table = $entity_type->getBaseTable()) && $this->viewsData->get($base_table) && $this->entityTypeManager->hasHandler($entity_type_id, 'view_builder')) {
        $this->derivatives[$entity_type_id] = [
          'title' => 'Content (with dynamic view mode)'
        ] + $this->derivatives[$entity_type_id];
      }
    }

    return $this->derivatives;
  }
}
