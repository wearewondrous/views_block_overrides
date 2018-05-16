<?php

namespace Drupal\views_block_overrides\Plugin\ViewsBlockConfigurationPlugin;

use Drupal\views_block_overrides\Plugin\ViewsBlockConfigurationPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\views\Views;
use Drupal\views_block_overrides\Plugin\views\row\EntityRowWithDynamicFormat;

/**
 * A views block configuration plugin that allows to pass exposed filters as
 * block configuration configuration.
 *
 * @ViewsBlockConfigurationPlugin(
 *   id = "dynamic_format",
 *   title = @Translation("Dynamic Format"),
 *   view_display = NULL
 * )
 */
class DynamicFormat extends ViewsBlockConfigurationPluginBase {

  /**
   * {@inheritdoc}
   */
  public function blockSettings(array $settings) {
    $settings = parent::blockSettings($settings);
    $settings[$this->pluginId]['format'] = 'default';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm(ViewsBlock $block, array $form, FormStateInterface $form_state) {
    $form = parent::blockForm($block, $form, $form_state);
    $view = $this->getView();
    $display = $this->getViewDisplay();
    $block_configuration = $block->getConfiguration();
    // Get the row plugin.
    $type = 'row';
    $option = $display->getOption($type);
    $name = $option['type'];
    $row_plugin_settings = [];
    $row_plugin = Views::pluginManager($type)->createInstance($name);
    // The row plugin needs to be initialized else the row plugin entityTypeId
    // value is not set and we can't fetch the format options.
    $row_plugin->init($view, $display, $row_plugin_settings);

    $options = method_exists($row_plugin, 'getFormatOptions') ? $row_plugin->getFormatOptions() : ['default' => $this->t('Default')];

    // Build the setting form.
    $form['override'][$this->pluginId]['format'] = [
      '#title' => $this->t('Format'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $block_configuration[$this->pluginId]['format'],
    ];

    return $form;
  }

}
