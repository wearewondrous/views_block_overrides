<?php

namespace Drupal\views_block_overrides\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for Views block configuration plugin plugins.
 */
abstract class ViewsBlockConfigurationPluginBase extends PluginBase implements ViewsBlockConfigurationPluginInterface {

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
    $options['allow']['contains'][$this->pluginId] = ['default' => $this->pluginId];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    $view_display = $this->configuration['view_display'];

    $filtered_allow = array_filter($view_display->getOption('allow'));
    $allowed_values = [];

    if (isset($filtered_allow[$this->pluginId])) {
      $options['allow']['value'] = empty($options['allow']['value'])
      ? $this->getTitle()
      : implode(', ', [
          $options['allow']['value'],
          $this->getTitle()
        ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['allow']['#options'][$this->pluginId] = $this->getTitle();
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    $section = $form_state->get('section');
    switch ($section) {
      case $this->pluginId:
        $this->configuration['view_display']->setOption($section, $form_state->getValue($section));
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSettings(array $settings) {
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
    $values = $form_state->getValue(['override']);
    return isset($values[$this->pluginId]) ? $values[$this->pluginId] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function preBlockBuild(ViewsBlock $block) {
  }


}
