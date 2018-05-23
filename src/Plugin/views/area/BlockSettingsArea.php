<?php

namespace Drupal\views_block_overrides\Plugin\views\area;

use Drupal\views\Plugin\views\area\AreaPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\views_block_overrides\Plugin\BlockSettingsPluginInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
/**
 * Generic area plugin to provide a common base for all BlockSettings Area plugin.
 *
 * @ViewsArea("views_block_overrides_area")
 */
class BlockSettingsArea extends AreaPluginBase {

  /** @var \Drupal\views_block_overrides\Plugin\BlockSettingsPluginInterface $block_settings_plugin */
  private $block_settings_plugin;

  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->block_settings_plugin = $this->getBlockSettingsPluginInstance();
  }

  /**
   * The entity object
   *
   * @var object
   */
  public $block_instance = NULL;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    if (!$this->block_settings_plugin) {
      return $options;
    }
    $context = [
      'area' => $this,
    ];
    $options = $this->block_settings_plugin->defineAreaOptions($options, $context);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    if (!$this->block_settings_plugin) {
      return;
    }

    $context = [
      'area' => $this,
    ];
    $this->block_settings_plugin->buildAreaOptionsForm($form, $form_state, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if (!$this->block_settings_plugin || !$this->block_settings_plugin->isAllowed()) {
      return [];
    }

    $context = [
      'area' => $this,
    ];

    return $this->block_settings_plugin->renderArea($context);
  }

  /**
   * Initialize the block settings plugin instance.
   *
   * @return \Drupal\views_block_overrides\Plugin\BlockSettingsPluginInterface|null|object
   */
  public function getBlockSettingsPluginInstance() {
    if (!isset($this->definition['block_settings_plugin_id'])) {
      return NULL;
    }

    /** @var \Drupal\views_block_overrides\Plugin\BlockSettingsPluginManager $block_settings_manager */
    $block_settings_manager = \Drupal::service('plugin.manager.block_settings');
    /** @var  \Drupal\views_block_overrides\Plugin\BlockSettingsPluginInterface $definition */
    $plugin_settings = [
      'view_display' => $this->view->getDisplay(),
    ];
    /** @var \Drupal\views_block_overrides\Plugin\BlockSettingsPluginInterface $block_settings_plugin */
    $block_settings_plugin = $block_settings_manager->createInstance($this->definition['block_settings_plugin_id'], $plugin_settings);

    if (!$block_settings_plugin instanceof BlockSettingsPluginInterface) {
      return NULL;
    }

    return $block_settings_plugin;
  }

}
