<?php

namespace Drupal\views_block_overrides\Plugin\BlockSettings;

use Drupal\views_block_overrides\Plugin\BlockSettingsPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\views\Views;
use Drupal\views_block_overrides\Plugin\views\row\EntityRowWithDynamicFormat;

/**
 * A views block configuration plugin that allows to pass exposed filters as
 * block configuration configuration.
 *
 * @BlockSettings(
 *   id = "dynamic_format",
 *   title = @Translation("Dynamic Format"),
 *   view_display = NULL,
 *   area = false
 * )
 */
class DynamicFormat extends BlockSettingsPluginBase {

  /**
   * {@inheritdoc}
   */
  public function blockSettings() {
    $settings = parent::blockSettings();
    $settings['format'] = 'default';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm(ViewsBlock $block, array $form, FormStateInterface $form_state) {
    $subform = parent::blockForm($block, $form, $form_state);
    $view = $this->getView();
    $display = $this->getViewDisplay();
    $block_configuration = $this->getBlockSettings();
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
    $subform['format'] = [
      '#title' => $this->getCustomLabel(),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $block_configuration['format'],
    ];

    return $subform;
  }

}
