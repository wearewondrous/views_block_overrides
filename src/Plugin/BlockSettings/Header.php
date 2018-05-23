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
 *   id = "header",
 *   title = @Translation("Header"),
 *   area = true
 * )
 */
class Header extends BlockSettingsPluginBase {

  /**
   * {@inheritdoc}
   */
  public function blockSettings(array $settings) {
    $settings = parent::blockSettings($settings);
    $settings[$this->pluginId]['header'] = NULL;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm(ViewsBlock $block, array $form, FormStateInterface $form_state) {
    $form = parent::blockForm($block, $form, $form_state);

    $block_configuration = $block->getConfiguration();
    $configuration = $block_configuration[$this->pluginId];

    $subform['header'] = [
      '#type' => 'details',
      '#title' => $this->t('Block header'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $subform['header']['headline'] = array(
      '#title' => $this->t('Headline'),
      '#type' => 'text_format',
      '#default_value' => $configuration['header']['headline']['value'],
      '#format' => $configuration['header']['headline']['format'],
    );

    $subform['header']['description'] = array(
      '#title' => $this->t('Description'),
      '#type' => 'text_format',
      '#default_value' => $configuration['header']['description']['value'],
      '#format' => $configuration['header']['description']['format'],
    );

    $subform['header']['link'] = array(
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#title' => $this->t('Link'),
      '#default_value' => isset($configuration['header']['link']) ? Node::load($configuration['header']['link']) : NULL,
    );

    $form['override'][$this->pluginId] = $subform;

    return $form;
  }
}
