<?php

namespace Drupal\views_block_overrides\Plugin\ViewsBlockConfigurationPlugin;

use Drupal\views_block_overrides\Plugin\ViewsBlockConfigurationPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\views\Views;
use Drupal\views_block_overrides\Plugin\views\row\EntityRowWithDynamicFormat;
use Drupal\node\Entity\Node;

/**
 * A views block configuration plugin that allows to pass exposed filters as
 * block configuration configuration.
 *
 * @ViewsBlockConfigurationPlugin(
 *   id = "node_reference",
 *   title = @Translation("Node reference"),
 * )
 */
class NodeReference extends ViewsBlockConfigurationPluginBase {

  /**
   * {@inheritdoc}
   */
  public function blockSettings(array $settings) {
    $settings = parent::blockSettings($settings);
    $settings[$this->pluginId]['reference'] = NULL;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm(ViewsBlock $block, array $form, FormStateInterface $form_state) {
    $form = parent::blockForm($block, $form, $form_state);

    $block_configuration = $block->getConfiguration();
    $default_value = NULL;
    if (isset($block_configuration[$this->pluginId]['reference'][0]['target_id'])) {
      $default_value = Node::load($block_configuration[$this->pluginId]['reference'][0]['target_id']);
    }

    $form['override'][$this->pluginId]['reference'] = array(
      '#title' => $this->t('Node reference'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#tags' => TRUE,
      '#default_value' => $default_value,
    );

    return $form;
  }

}
