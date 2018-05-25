<?php

namespace Drupal\views_block_overrides\Plugin\BlockSettings;

use Drupal\views_block_overrides\Plugin\BlockSettingsPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\node\Entity\Node;
use Drupal\Component\Utility\NestedArray;

/**
 * A views block configuration plugin that allows to pass exposed filters as
 * block configuration configuration.
 *
 * @BlockSettings(
 *   id = "entity_reference",
 *   title = @Translation("Entity reference"),
 *   area = true
 * )
 */
class EntityReference extends BlockSettingsPluginBase {

  use EntityReferenceTrait;

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

    $settings = $this->configuration['view_display']->getOption($this->pluginId);

    $subform['reference'] = array(
      '#title' => $this->getCustomLabel(),
      '#type' => 'entity_autocomplete',
      '#target_type' => $settings['target_type'],
      '#tags' => TRUE,
      '#default_value' => $default_value,
      '#selection_handler' => $settings['selection_handler'],
      '#selection_settings' => $settings['selection_settings']
    );

    //return $subform;
  }

  /**
   * Provide the summary for page options in the views UI.
   *
   * This output is returned as an array.
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    $options[$this->pluginId] = [
      'category' => 'block',
      'title' => $this->getTitle(),
      'value' => $this->t('Settings'),
    ];

  }

  /**
   * Provide the default form for setting options.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $subform = $this->buildEntityReferenceSettingsForm($form, $form_state);
    return $subform;
  }
}
