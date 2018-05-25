<?php

namespace Drupal\views_block_overrides\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for Views block configuration plugin plugins.
 */
abstract class BlockSettingsPluginBase extends PluginBase implements BlockSettingsPluginInterface {

  use StringTranslationTrait;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pluginDefinition['view_display'] = $this->configuration['view_display'];
    $this->pluginDefinition['view'] = $this->configuration['view_display']->view;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function areaEnabled() {
    return $this->pluginDefinition['area'];
  }

  /**
   * {@inheritdoc}
   */
  public function getView() {
    return $this->pluginDefinition['view'];
  }

  /**
   * {@inheritdoc}
   */
  public function getViewDisplay() {
    return $this->pluginDefinition['view_display'];
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    $value = $form_state->getValue($this->pluginId);
    $this->configuration['view_display']->setOption($this->pluginId, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionsFormSettings() {
    return $this->configuration['view_display']->getOption($this->pluginId);
  }

  /**
   * {@inheritdoc}
   */
  public function blockSettings() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm(ViewsBlock $block, array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit(ViewsBlock $block, $form, FormStateInterface $form_state) {
    $display = $this->getViewDisplay();

    $values = $form_state->getValue([$display->getPluginId(), $this->pluginId]);
    return isset($values) ? $values : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function preBlockBuild(ViewsBlock $block) {
  }

  /**
   * {@inheritdoc}
   */
  public function defineAreaOptions(array $context) {
    return [];
  }

  /**
   * {@inheritdoc
   */
  public function buildAreaOptionsForm(array &$form, FormStateInterface $form_state, array $context) {
  }

  /**
   * {@inheritdoc}
   */
  public function renderArea(array $context) {
    if (!$this->isAllowed()) {
      return [];
    }

    $context['block_settings_plugin'] = $this;
    $context['view'] = $this->configuration['view_display']->view;
    $block_settings = $this->getBlockSettings();

    return [
      '#theme' => 'views_block_overrides_area',
      '#settings' => $block_settings,
      '#context' => $context,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockSettings() {
    if (!($block_instance = $this->getBlockInstance())) {
      return NULL;
    }
    $block_settings = $block_instance->getConfiguration();
    $display = $this->getViewDisplay();
    return isset($block_settings[$display->getPluginId()][$this->pluginId]) ? $block_settings[$display->getPluginId()][$this->pluginId] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockInstance() {
    $view = $this->configuration['view_display']->view;
    return (isset($view->views_block_overrides['block_instance'])) ? $view->views_block_overrides['block_instance'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayOptions() {
    $display = $this->getViewDisplay();
    return $display->getOption($this->pluginId);
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowed() {
    return $this->configuration['view_display']->isAllowed($this->pluginId);
  }

  /**
   * Get's the custom label from the view display.
   *
   * @return string
   *   The label.
   */
  public function getCustomLabel() {
    $display_settigns = $this->getViewDisplay()->getSettings($this->pluginId);
    $custom_label = trim($display_settigns['custom_label']);
    return $custom_label ? $custom_label : $this->getTitle();
  }
}
