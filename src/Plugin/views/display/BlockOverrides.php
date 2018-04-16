<?php

namespace Drupal\views_block_overrides\Plugin\views\display;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\views\Plugin\views\display\Block;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\taxonomy\Entity\Term;

/**
 * A block plugin that allows exposed filters to be configured.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "field_block_filter_block",
 *   title = @Translation("Block Overrides"),
 *   help = @Translation("Allows block displays to override block configuration.
 *   "), theme = "views_view", register_theme = FALSE, uses_hook_block
 *   = TRUE, contextual_links_locations = {"block"}, admin = @Translation("Overrides
 *   Block")
 * )
 *
 * @see \Drupal\views\Plugin\block\block\ViewsBlock
 * @see \Drupal\views\Plugin\Derivative\ViewsBlock
 */
class BlockOverrides extends Block {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['allow']['contains']['exposed_filter'] = ['default' => 'exposed_filter'];
    $options['allow']['contains']['exposed_sort'] = ['default' => 'exposed_sort'];
    $options['allow']['contains']['contextual_filter'] = ['default' => 'contextual_filter'];
    return $options;
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
    $settings['exposed_filter'] = [];

    // These items must be exposed to be overriden.
    foreach (['filter', 'sort'] as $type) {
      $items = $this->view->display_handler->getHandlers($type);
      foreach ($items as $id => $item) {
        if (!$item->options['exposed']) {
          continue;
        }
        $settings['exposed_' . $type][$id]['enabled'] = FALSE;
        $settings['exposed_' . $type][$id]['value'] = '';
      }
    }

    // All contextual filters can be overridden.
    $contextual_filters = $this->view->display_handler->getHandlers('argument');
    foreach ($contextual_filters as $id => $contextual_filter) {
      $settings['contextual_filter'][$id]['enabled'] = FALSE;
      $settings['contextual_filter'][$id]['value'] = '';
    }
    return $settings;
  }

  /**
   * Provide the summary for page options in the views UI.
   *
   * This output is returned as an array.
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    // @todo: make this more general and not reliant on the fact that
    // items_per_page is currently the only allowed block config setting.
    $filtered_allow = array_filter($this->getOption('allow'));
    $allowed = [];
    if (isset($filtered_allow['items_per_page'])) {
      $allowed[] = $this->t('Items per page');
    }
    if (isset($filtered_allow['exposed_filter'])) {
      $allowed[] = $this->t('Exposed filters');
    }
    if (isset($filtered_allow['exposed_sort'])) {
      $allowed[] = $this->t('Exposed sorts');
    }
    if (isset($filtered_allow['contextual_filter'])) {
      $allowed[] = $this->t('Contextual filters');
    }
    $options['allow'] = [
      'category' => 'block',
      'title' => $this->t('Allow settings'),
      'value' => empty($allowed) ? $this->t('None') : implode(', ', $allowed),
    ];
  }

  /**
   * Adds the configuration form elements specific to this views block plugin.
   *
   * This method allows block instances to override the views exposed filters.
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
    parent::blockForm($block, $form, $form_state);

    $allow_settings = array_filter($this->getOption('allow'));
    $block_configuration = $block->getConfiguration();

    foreach ($allow_settings as $type => $enabled) {
      if (empty($enabled)) {
        continue;
      }

      switch ($type) {
        case "exposed_filter":
          $handlers = $this->view->display_handler->getHandlers('filter');
          $label = $this->t('Use exposed filter');
          break;
        case "contextual_filter":
          $handlers = $this->view->display_handler->getHandlers('argument');
          $label = $this->t('Use contextual filter');
          break;
        case "exposed_sort":
          $handlers = $this->view->display_handler->getHandlers('sort');
          $label = $this->t('Use contextual filter exposed sort');
          break;
        default:
          // Continue the loop.
          continue 2;
          break;
      }

      foreach ($handlers as $id => $handler) {
        if ($type != 'contextual_filter' && !$handler->options['exposed']) {
          continue;
        }

        $form['override'][$type][$id]['enabled'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('@type: @id', [
            '@id' => $handler->definition['title'],
            '@type' => $label,
          ]),
          '#default_value' => $block_configuration[$type][$id]['enabled'],
        ];

        // Generate filter input.
        $default_value = $block_configuration[$type][$id]['value'] ?: NULL;
        $element = &$form['override'][$type][$id]['value'];
        switch ($type) {
          case 'exposed_filter':
            $value = $this->getExposedPossibleValues($id, $handler);
            break;
          case 'contextual_filter':
            $value = $this->getContextualPossibleValues($id, $handler);
            $default_value = $default_value ?: $handler->options['exception']['value'];
            break;
          default:
            $value = FALSE;
            break;
        }
        $element = $this->getFormElement($id, $handler, $default_value, $value);
        $element['#states'] = [
          'visible' => [
            [
              ':input[name="settings[override][' . $type . '][' . $id . '][enabled]"]' => ['checked' => TRUE],
            ],
          ],
        ];
      }
    }

    return $form;
  }

  /**
   * Define a text form input.
   */
  public function getTextfieldElement($id, $handler, $default_value) {
    return  [
      '#title' => $this->t('Value for %label', ['%label' => $handler->definition['title']]),
      '#type' => 'textfield',
      '#default_value' => $default_value,
    ];
  }

  /**
   * Define a select form input.
   */
  public function getOptionsElement($handler, $default_value, $options) {
    return  [
      '#title' => $this->t('Value for %label', ['%label' => $handler->definition['title']]),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $default_value,
    ];
  }

  /**
   * Generate the form input.
   */
  public function getFormElement($id, $handler, $default_value, $value) {
    if (is_array($value)) {
      $element = $this->getOptionsElement($handler, $default_value, $value);
    }
    else {
      $element = $this->getTextfieldElement($id, $handler, $default_value);
    }

    return $element;
  }

  /**
   * Loads the possible exposed filter values/options.
   */
  function getExposedPossibleValues($id, $handler) {
    $values = FALSE;

    if (isset($handler->options['vid'])) {
      $values = [];
      $query = \Drupal::entityQuery('taxonomy_term')
        // @todo Sorting on vocabulary properties -
        //   https://www.drupal.org/node/1821274.
        ->sort('weight')
        ->sort('name')
        ->addTag('taxonomy_term_access');
      if ($handler->options['limit']) {
        $query->condition('vid', $handler->options['vid']);
      }
      $terms = Term::loadMultiple($query->execute());
      foreach ($terms as $term) {
        $values[$term->id()] = \Drupal::entityManager()->getTranslationFromContext($term)->label();
      }
    }

    return $values;
  }

  /**
   * Loads the possible contextual filter values/options.
   */
  function getContextualPossibleValues($id, $handler) {
    $validation_type = $this->getContextualFilterValidationType($id);
    $values = FALSE;

    switch ($validation_type) {
      case "entity:taxonomy_term":
        $options = $handler->options;
        $validate_bundles = $options['validate_options']['bundles'];
        $values = [
          $options['exception']['value'] => $options['exception']['title'],
        ];
        foreach ($validate_bundles as $bundle) {
          $terms = \Drupal::entityManager()
            ->getStorage('taxonomy_term')
            ->loadTree($bundle);
          foreach ($terms as $term) {
            $values[$term->tid] = $term->name;
          }
        }
        break;
    }

    return $values;
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

    $overides = $form_state->getValue(['override']);
    $config = $block->getConfiguration();

    foreach ($overides as $type => $values) {
      foreach ($values as $id => $settings) {
        $config[$type][$id] = [
          'enabled' => $settings['enabled'],
          'value' => $settings['value'],
        ];
        $form_state->unsetValue(['override', $type, $id]);
      }
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
    $this->setExposedFilters($block);
    $this->setContextualFilters($block);
  }

  /**
   * Sets the exposed filters
   */
  public function setExposedFilters($block) {
    $config = $block->getConfiguration();
    $exposedInput = [];
    foreach (['exposed_filter', 'exposed_sort'] as $type) {
      if (!empty($config[$type])) {
        foreach ($config[$type] as $id => $values) {
          if ($values['enabled']) {
            $exposedInput[$id] = $values['value'];
          }
        }
      }
    }
    $this->view->exposed_data = $exposedInput;
  }

  /**
   * Sets the contextual filters.
   *
   * @param $block
   */
  public function setContextualFilters($block) {
    $config = $block->getConfiguration();
    if (!empty($config['contextual_filter'])) {
      foreach ($config['contextual_filter'] as $id => $values) {
        if ($values['enabled']) {
          $contextual_filter_value = $values['value'];
          $contextual_filter_type = $this->getContextualFilterValidationType($id);
          $context_definition = new ContextDefinition($contextual_filter_type, $id);
          $context_definition->setDefaultValue($contextual_filter_value);
          $block->setContext($id, new Context($context_definition, $contextual_filter_value));
        }
      }
    }
  }

  /**
   * Gets the contextual filter validation type
   */
  public function getContextualFilterValidationType($id) {
    // TODO Can we use string as default?
    $default = 'string';

    $handler = $this->view->display_handler->getHandler('argument', $id);
    if (isset($handler->options['validate']['type'])) {
      $options = $handler->options;
      $validate_type = $options['validate']['type'];
    }
    return $validate_type != "none" ? $validate_type : $default;
  }

  /**
   * Block views use exposed widgets only if AJAX is set.
   */
  public function usesExposed() {
    if ($this->ajaxEnabled()) {
      return parent::usesExposed();
    }
    return FALSE;
  }

  /**
   * Provide the default form for setting options.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    if ($form_state->get('section') == 'allow') {
      $form['allow']['#options']['exposed_filter'] = $this->t('Exposed filters');
    }
  }

}
