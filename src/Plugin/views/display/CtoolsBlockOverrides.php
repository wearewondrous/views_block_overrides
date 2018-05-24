<?php

namespace Drupal\views_block_overrides\Plugin\views\display;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\ctools_views\Plugin\Display\Block as CtoolBlock;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A block plugin that allows exposed filters to be configured.
 *
 * @deprecated this class will be removed, kept only for backward compatibility
 *
 * @ingroup view_display_plugins
 *
 * @ViewsDisplay(
 *   id = "views_block_overrides",
 *   title = @Translation("Block Overrides"),
 *   help = @Translation("Allows block displays to override block configuration."),
 *   theme = "views_view",
 *   register_theme = FALSE,
 *   uses_hook_block = TRUE,
 *   contextual_links_locations = {"block"},
 *   admin = @Translation("Block Overrides")
 * )
 */
class CtoolsBlockOverrides extends CtoolBlock {

  /**
   * The entity manager.
   *
   * @var \Drupal\views_block_overrides\Plugin\BlockSettingsPluginManager
   */
  protected $configurationPluginManager;

  /**
   * The plugin definitions.
   *
   * @var array
   */
  protected $pluginDefinitions;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, $configuration_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager);

    $this->configurationPluginManager = $configuration_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('plugin.manager.block_settings')
    );
  }

  /**
   * Loads the views block configuration plugin definitions.
   *
   * @return array|mixed[]|null
   */
  public function getBlockSettingsDefinitions() {
    return  ($this->pluginDefinitions) ?: ($this->pluginDefinitions = $this->configurationPluginManager->getDefinitions());
  }

  /**
   * Get's a views block configuration plugin instance.
   *
   * @param $plugin_id
   *   The plugin id.
   * @param array $settings
   *   The plugin settings.
   *
   * @return object
   */
  public function getBlockSettingsInstance($plugin_id, $settings = []) {
    $plugin_settings = $settings + [
      'view_display' => $this,
    ];
    return $this->configurationPluginManager->createInstance($plugin_id, $plugin_settings);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $plugin_definitions = $this->getBlockSettingsDefinitions();
    foreach ($plugin_definitions as $id => $definition) {
      $plugin = $this->getBlockSettingsInstance($id);
      $options = array_merge_recursive($options, $plugin->defineOptions());
    }

    return $options;
  }

  /**
   * Provide the summary for page options in the views UI.
   *
   * This output is returned as an array.
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    $plugin_definitions = $this->getBlockSettingsDefinitions();
    foreach ($plugin_definitions as $id => $definition) {
      if (!$this->isAllowed($id)) {
        continue;
      }
      /** @var \Drupal\views_block_overrides\Plugin\BlockSettingsPluginInterface $plugin */
      $plugin = $this->getBlockSettingsInstance($id);
      $plugin->optionsSummary($categories, $options);
    }
  }

  /**
   * Provide the default form for setting options.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $plugin_definitions = $this->getBlockSettingsDefinitions();
    foreach ($plugin_definitions as $id => $definition) {
      $plugin = $this->getBlockSettingsInstance($id);
      $plugin->buildOptionsForm($form, $form_state);
      if (isset($form[$plugin->pluginId])) {
        $form[$plugin->pluginId]['#tree'] = TRUE;
      }
    }
  }

  /**
   * Perform any necessary changes to the form values prior to storage.
   * There is no need for this function to actually store the data.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);

    $plugin_definitions = $this->getBlockSettingsDefinitions();
    foreach ($plugin_definitions as $id => $definition) {
      $plugin = $this->getBlockSettingsInstance($id);
      $plugin->submitOptionsForm($form, $form_state);
    }
  }

  /**
   * Returns plugin-specific settings for the block.
   *
   * @param array $settings
   *   The settings of the block.
   *
   * @return array
   *   An array of block-specific settings to override the defaults provided in
   *   \Drupal\views\Plugin\Block\ViewsBlock::defaultConfiguration().
   *
   * @see \Drupal\views\Plugin\Block\ViewsBlock::defaultConfiguration()
   */
  public function blockSettings(array $settings) {
    $settings = parent::blockSettings($settings);

    $plugin_definitions = $this->getBlockSettingsDefinitions();
    foreach ($plugin_definitions as $id => $definition) {
      $plugin = $this->getBlockSettingsInstance($id);
      $settings = array_merge_recursive($settings, $plugin->blockSettings($settings));
    }

    return $settings;
  }

  /**
   * Adds the configuration form elements specific to this views block plugin.
   *
   * @param \Drupal\views\Plugin\Block\ViewsBlock $block
   *   The ViewsBlock plugin.
   * @param array $form
   *   The form definition array for the block configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array $form
   *   The renderable form array representing the entire configuration form.
   *
   * @see \Drupal\views\Plugin\Block\ViewsBlock::blockForm()
   */
  public function blockForm(ViewsBlock $block, array &$form, FormStateInterface $form_state) {
    $form = parent::blockForm($block, $form, $form_state);

    $plugin_definitions = $this->getBlockSettingsDefinitions();
    foreach ($plugin_definitions as $id => $definition) {
      if (!$this->isAllowed($id)) {
        continue;
      }
      $plugin = $this->getBlockSettingsInstance($id);
      $form = array_merge_recursive($form, $plugin->blockForm($block, $form, $form_state));
    }

    return $form;
  }

  /**
   * Handles form submission for the views block configuration form.
   *
   * @param \Drupal\views\Plugin\Block\ViewsBlock $block
   *   The ViewsBlock plugin.
   * @param array $form
   *   The form definition array for the full block configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\views\Plugin\Block\ViewsBlock::blockSubmit()
   */
  public function blockSubmit(ViewsBlock $block, $form, FormStateInterface $form_state) {
    parent::blockSubmit($block, $form, $form_state);

    $plugin_definitions = $this->getBlockSettingsDefinitions();
    $config = $block->getConfiguration();
    foreach ($plugin_definitions as $id => $definition) {
      if (!$this->isAllowed($id)) {
        continue;
      }
      $plugin = $this->getBlockSettingsInstance($id);
      $config[$id] = $plugin->blockSubmit($block, $form, $form_state);
    }
    $block->setConfiguration($config);
  }

  /**
   * Allows to change the display settings right before executing the block.
   *
   * @param \Drupal\views\Plugin\Block\ViewsBlock $block
   *   The block plugin for views displays.
   */
  public function preBlockBuild(ViewsBlock $block) {
    parent::preBlockBuild($block);

    $plugin_definitions = $this->getBlockSettingsDefinitions();
    foreach ($plugin_definitions as $id => $definition) {
      if (!$this->isAllowed($id)) {
        continue;
      }
      $plugin = $this->getBlockSettingsInstance($id);
      $plugin->preBlockBuild($block);
    }
  }

  /**
   * Checks if the config plugin is allowed form the views settings.
   *
   * @return bool
   *   TRUE if it's allowed
   */
  public function isAllowed($plugin_id) {
    $options = array_filter($this->getOption('allow'));
    return isset($options[$plugin_id]);
  }

}
