<?php

namespace Drupal\views_block_overrides\Plugin\views\display;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\ctools_views\Plugin\Display\Block as CtoolBlock;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\SortArray;

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
  public function getBlockSettingsInstance($plugin_id) {
    $plugins = $this->getBlockSettingsInstances();
    return $plugins[$plugin_id];
  }

  /**
   * Get's the list of existing views block settings.
   *
   * @param array $settings
   *   The plugin settings.
   *
   * @return array
   *   The list of block settings.
   */
  public function getBlockSettingsInstances($settings = []) {
    $plugins = drupal_static(__FUNCTION__);

    if ($plugins) {
      return $plugins;
    }

    $plugin_settings = $settings + [
        'view_display' => $this,
      ];

    $plugin_definitions = $this->getBlockSettingsDefinitions();
    foreach ($plugin_definitions as $plugin_id => $definition) {
      $plugins[$plugin_id] = $this->configurationPluginManager->createInstance($plugin_id, $plugin_settings);
    }

    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $settings = [];
    $weight = 0;
    foreach ($this->getBlockSettingsInstances() as $id => $plugin) {
      $options = array_merge_recursive($options, $plugin->defineOptions());

      // Prepare the settings.
      $settings[$id] = [
        'custom_label' => $plugin->getTitle(),
        'enabled' => 0,
        'weight' => $weight++,
      ];
    }

    $options[$this->getPluginId()] = ['default' => $settings];

    return $options;
  }

  /**
   * Provide the summary for page options in the views UI.
   *
   * This output is returned as an array.
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    $options[$this->getPluginId()] = [
      'category' => 'block',
      'title' => $this->pluginTitle(),
      'value' => $this->t('Settings'),
    ];

    foreach ($this->getBlockSettingsInstances() as $id => $plugin) {
      if (!$this->isAllowed($id)) {
        continue;
      }
      /** @var \Drupal\views_block_overrides\Plugin\BlockSettingsPluginInterface $plugin */
      $plugin->optionsSummary($categories, $options);
    }
  }

  /**
   * Provide the default form for setting options.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Builds the main settings form.
    $this->buildMainOptionsForm($form, $form_state);
    $section = $form_state->get('section');
    foreach ($this->getBlockSettingsInstances() as $id => $plugin) {
      switch ($section) {
        case $id:
          $form[$plugin->pluginId] = $plugin->buildOptionsForm($form, $form_state);
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

    $section = $form_state->get('section');
    switch ($section) {
      case $this->getPluginId():
        // Submit for the main options form.
        $this->setOption($section, $form_state->getValue($section));
        break;
    }

    foreach ($this->getBlockSettingsInstances() as $id => $plugin) {
      if (!$this->isAllowed($id)) {
        continue;
      }
      $section = $form_state->get('section');
      switch ($section) {
        case $id:
          // Call the current section plugin submit.
          $plugin->submitOptionsForm($form, $form_state);
          break;
      }
    }
  }

  /**
   * Build the main settings form.
   */
  public function buildMainOptionsForm(&$form, FormStateInterface $form_state) {
    $section = $form_state->get('section');
    switch ($section) {
      case $this->getPluginId():

        $settings = $this->getOption($this->getPluginId());

        $form[$this->getPluginId()][$this->getPluginId()] = [
          '#type' => 'table',
          '#header' => [
            $this->t('Block settings'),
            $this->t('Weight'),
            $this->t('Enabled'),
            $this->t('Custom Label'),
          ],
          '#empty' => $this->t('No displays available.'),
          '#tabledrag' => [
            [
              'action' => 'order',
              'relationship' => 'sibling',
              'group' => 'table-sort-weight',
            ]
          ],
        ];

        uasort($settings, function ($a, $b) {
          return SortArray::sortByWeightElement($a, $b);
        });

        $table = &$form[$this->getPluginId()][$this->getPluginId()];
        foreach ($settings as $id => $value) {
          $plugin = $this->getBlockSettingsInstance($id);
          // Add tableDrag support:
          $table[$id]['#attributes']['class'][] = 'draggable';
          $table[$id]['#weight'] = $value['weight'];

          $table[$id]['label'] = [
            '#type' => 'label',
            '#title' => $plugin->getTitle(),
          ];

          // TableDrag: Weight column element.
          $table[$id]['weight'] = [
            '#type' => 'weight',
            '#title' => $this
              ->t('Weight for @title', [
                '@title' => $plugin->getTitle(),
              ]),
            '#title_display' => 'invisible',
            '#default_value' => $value['weight'],
            // Classify the weight element for #tabledrag.
            '#attributes' => [
              'class' => [
                'table-sort-weight',
              ],
            ],
          ];

          $table[$id]['enabled'] = [
            '#type' => 'checkbox',
            '#default_value' => $value['enabled'],
          ];

          $table[$id]['custom_label'] = [
            '#type' => 'textfield',
            '#default_value' => $value['custom_label']
          ];
        }

        break;
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

    foreach ($this->getBlockSettingsInstances() as $id => $plugin) {
      $settings[$this->getPluginId()][$id] = $plugin->blockSettings();
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

    $settings = $this->getOption($this->getPluginId());

    $form[$this->getPluginId()] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Block overrides'),
    ];

    foreach ($this->getBlockSettingsInstances() as $id => $plugin) {
      if (!$this->isAllowed($id)) {
        continue;
      }
      $form[$this->getPluginId()][$id] = $plugin->blockForm($block, $form, $form_state);
      $form[$this->getPluginId()][$id]['#weight'] = $settings[$id]['weight'];
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

    $config = $block->getConfiguration();
    foreach ($this->getBlockSettingsInstances() as $id => $plugin) {
      if (!$this->isAllowed($id)) {
        continue;
      }
      $config[$this->getPluginId()][$id] = $plugin->blockSubmit($block, $form, $form_state);
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

    foreach ($this->getBlockSettingsInstances() as $id => $plugin) {
      if (!$this->isAllowed($id)) {
        continue;
      }
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
    $options = array_filter($this->getOption($this->getPluginId()));
    return $options[$plugin_id]['enabled'];
  }


  /**
   * Checks if the config plugin is allowed form the views settings.
   *
   * @return bool
   *   TRUE if it's allowed
   */
  public function getSettings($plugin_id) {
    $options = array_filter($this->getOption($this->getPluginId()));
    return $options[$plugin_id];
  }
}
