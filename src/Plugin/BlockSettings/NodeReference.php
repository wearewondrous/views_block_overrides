<?php

namespace Drupal\views_block_overrides\Plugin\BlockSettings;

use Drupal\views_block_overrides\Plugin\BlockSettingsPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\views\Views;
use Drupal\views_block_overrides\Plugin\views\row\EntityRowWithDynamicFormat;
use Drupal\node\Entity\Node;

/**
 * A views block configuration plugin that allows to pass exposed filters as
 * block configuration configuration.
 *
 * @BlockSettings(
 *   id = "node_reference",
 *   title = @Translation("Node reference"),
 *   area = true
 * )
 */
class NodeReference extends BlockSettingsPluginBase {

  /**
   * {@inheritdoc}
   */
  public function blockSettings() {
    $settings = parent::blockSettings();
    $settings['reference'] = NULL;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm(ViewsBlock $block, array $form, FormStateInterface $form_state) {
    $subform = parent::blockForm($block, $form, $form_state);

    $block_configuration = $this->getBlockSettings();
    $default_value = NULL;
    if (isset($block_configuration['reference'][0]['target_id'])) {
      $default_value = Node::load($block_configuration['reference'][0]['target_id']);
    }

    $subform['reference'] = array(
      '#title' => $this->getCustomLabel(),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#tags' => TRUE,
      '#default_value' => $default_value,
    );

    return $subform;
  }
}
