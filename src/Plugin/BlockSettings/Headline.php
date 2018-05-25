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
 *   id = "headline",
 *   title = @Translation("Headline"),
 *   area = true
 * )
 */
class Headline extends BlockSettingsPluginBase {

  /**
   * {@inheritdoc}
   */
  public function blockSettings() {
    $settings = parent::blockSettings();
    $settings['headline'] = NULL;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm(ViewsBlock $block, array $form, FormStateInterface $form_state) {
    $subform = parent::blockForm($block, $form, $form_state);

    $block_configuration = $this->getBlockSettings();
    $display_options = $this->getDisplayOptions();

    $subform['headline'] = [
      '#type' => 'details',
      '#title' => $this->getCustomLabel(),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $subform['headline']['use_global'] = array(
      '#title' => $this->t('Use global Headline settings'),
      '#type' => 'checkbox',
      '#default_value' => $block_configuration['headline']['use_global'],
    );

    $subform['headline']['title'] = array(
      '#title' => $this->t('Title'),
      '#type' => 'text_format',
      '#default_value' => isset($block_configuration['headline']['title']['value']) ? $block_configuration['headline']['title']['value'] : $display_options['title']['value'],
      '#format' => isset($block_configuration['headline']['title']['format']) ? $block_configuration['headline']['title']['format'] : $display_options['title']['format'],
    );

    $subform['headline']['description'] = array(
      '#title' => $this->t('Description'),
      '#type' => 'text_format',
      '#default_value' => isset($block_configuration['headline']['description']['value']) ? $block_configuration['headline']['description']['value'] : $display_options['description']['value'],
      '#format' => isset($block_configuration['headline']['description']['format']) ? $block_configuration['headline']['description']['format'] : $display_options['description']['format'],
    );

    $nid = isset($block_configuration['headline']['link']) ? $block_configuration['headline']['link'] : $display_options['link'];

    $subform['headline']['link'] = array(
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#title' => $this->t('Link'),
      '#default_value' => $nid ? Node::load($nid) : NULL,
    );

    return $subform;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit(ViewsBlock $block, $form, FormStateInterface $form_state) {
    $values = parent::blockSubmit($block, $form, $form_state);
    if (isset($values['headline']['use_global']) && $values['headline']['use_global']) {
      $display_options = $this->getDisplayOptions();
      foreach ($values['headline'] as $key => $value) {
        if (!isset($display_options[$key])) {
          continue;
        }
        $values['headline'][$key] = $display_options[$key];
      }
    }

    return $values;
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
    $subform = parent::buildOptionsForm($form, $form_state);
    $settings = $this->getOptionsFormSettings();
    $subform['title'] = [
      '#title' => $this->t('Title'),
      '#type' => 'text_format',
      '#default_value' => $settings['title']['value'],
      '#format' => $settings['title']['format'],
    ];

    $subform['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'text_format',
      '#default_value' => $settings['description']['value'],
      '#format' => $settings['description']['format'],
    ];

    $subform['link'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#title' => $this->t('Link'),
      '#default_value' => isset($settings['link']) ? Node::load($settings['link']) : NULL,
    ];

    return $subform;
  }
}
