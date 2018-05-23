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
 *   id = "inline_entity",
 *   title = @Translation("Inline entity"),
 *   area = true
 * )
 */
class InlineEntity extends BlockSettingsPluginBase {

  /**
   * {@inheritdoc}
   */
  public function blockSettings(array $settings) {
    $settings = parent::blockSettings($settings);
    $settings[$this->pluginId]['target_entity'] = NULL;

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

    $form['override'][$this->pluginId]['target_entity'] = [
      '#type' => 'inline_entity_form',
      '#entity_type' => 'block_overrides',
       '#bundle' => 'achievements',
       // If the #default_value is NULL, a new entity will be created.
       '#default_value' => $default_value,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit(ViewsBlock $block, $form, FormStateInterface $form_state) {
    $values = $form_state->getValue(['override']);
    return isset($values[$this->pluginId]) ? $values[$this->pluginId] : NULL;
  }

}
