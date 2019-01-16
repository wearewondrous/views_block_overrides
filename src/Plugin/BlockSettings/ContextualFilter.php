<?php

namespace Drupal\views_block_overrides\Plugin\BlockSettings;

use Drupal\views_block_overrides\Plugin\BlockSettingsPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\taxonomy\Entity\Term;

/**
 * A views block configuration plugin that allows to pass exposed filters as
 * block configuration configuration.
 *
 * @BlockSettings(
 *   id = "contextual_filter",
 *   title = @Translation("Contextual Filter"),
 *   view_display = NULL,
 *   area = false
 * )
 */
class ContextualFilter extends BlockSettingsPluginBase {

  /**
   * {@inheritdoc}
   */
  public function blockSettings() {
    $settings = parent::blockSettings();

    // All contextual filters can be overridden.
    $contextual_filters = $this->getView()->display_handler->getHandlers('argument');
    foreach ($contextual_filters as $id => $contextual_filter) {
      $settings['contextual_filter'][$id]['enabled'] = FALSE;
      $settings['contextual_filter'][$id]['value'] = '';
    }
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm(ViewsBlock $block, array $form, FormStateInterface $form_state) {
    $subform = parent::blockForm($block, $form, $form_state);

    $block_configuration = $this->getBlockSettings();
    $label = $this->t('Use contextual filter');

    $handlers = $this->getView()->display_handler->getHandlers('argument');
    foreach ($handlers as $id => $handler) {
      $subform['contextual_filter'][$id]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('@type: @id', [
          '@id' => $handler->definition['title'],
          '@type' => $label,
        ]),
        '#default_value' => $block_configuration['contextual_filter'][$id]['enabled'],
      ];

      // Generate filter input.
      $default_value = $block_configuration['contextual_filter'][$id]['value'] ?: NULL;
      $element = &$subform['contextual_filter'][$id]['value'];

      $value = $this->getContextualPossibleValues($id, $handler);
      $default_value = $default_value ?: $handler->options['exception']['value'];


      $element = $this->getFormElement($id, $handler, $default_value, $value);
      $element['#states'] = [
        'visible' => [
          [
            ':input[name="settings[' . $id . '][enabled]"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    return $subform;
  }

  /**
   * Define a text form input.
   */
  public function getTextfieldElement($id, $handler, $default_value) {
    return [
      '#title' => $this->t('Value for %label', ['%label' => $handler->definition['title']]),
      '#type' => 'textfield',
      '#default_value' => $default_value,
    ];
  }

  /**
   * Define a select form input.
   */
  public function getOptionsElement($handler, $default_value, $options, $multiple = FALSE) {
    return [
      '#title' => $this->t('Value for %label', ['%label' => $handler->definition['title']]),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $multiple ? explode($this->getMultiValueSeparator(), $default_value) : $default_value,
      '#multiple' => $multiple,
    ];
  }

  /**
   * Generate the form input.
   */
  public function getFormElement($id, $handler, $default_value, $value) {
    if (is_array($value)) {
      $element = $this->getOptionsElement($handler, $default_value, $value, $handler->options['break_phrase']);
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
        $values[$term->id()] = \Drupal::entityManager()
          ->getTranslationFromContext($term)
          ->label();
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
      // TODO more generic way to work with other entity types as well.
      case "entity:node":
        list($entity, $entity_type) = explode(':', $validation_type);
        $options = $handler->options;
        $validate_bundles = $options['validate_options']['bundles'];
        $values = [
          $options['exception']['value'] => $options['exception']['title'],
        ];
        $selection_plugin_manager = \Drupal::service('plugin.manager.entity_reference_selection');
        switch ($entity_type) {
          case "node":
            $selection_plugin_id = implode(':', ['default', $entity_type]);
            break;
          default:
            $selection_plugin_id = FALSE;
            break;
        }
        $plugin_configuration = [
          'target_type' => $entity_type,
          'target_bundles' => $validate_bundles,
        ];

        $entities = [];
        if ($selection_plugin_id && ($selection_plugin = $selection_plugin_manager->createInstance($selection_plugin_id, $plugin_configuration)) && $selection_plugin->getPluginId() != 'broken') {
          $entities = $selection_plugin->getReferenceableEntities();
        }
        foreach ($entities as $bundle => $entities) {
          foreach ($entities as $id => $label) {
            $values[$id] = $label . " ($id, $bundle)";
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
    $values = parent::blockSubmit($block, $form, $form_state);

    if (!isset($values)) {
      return;
    }

    foreach ($values as $id => $settings) {
      foreach ($settings as $filter_id => $value) {
        $config[$id][$filter_id] = [
          'enabled' => $settings[$filter_id]['enabled'],
          'value' => is_array($settings[$filter_id]['value']) ? implode($this->getMultiValueSeparator(), $settings[$filter_id]['value']) : $settings[$filter_id]['value'],
        ];
        $form_state->unsetValue([$this->pluginId, $id]);
      }
    }
    return $config;
  }

  /**
   * Allows to change the display settings right before executing the block.
   *
   * @param \Drupal\views\Plugin\Block\ViewsBlock $block
   *   The block plugin for views displays.
   */
  public function preBlockBuild(ViewsBlock $block) {
    $this->setContextualFilters($block);
  }

  /**
   * Sets the contextual filters.
   *
   * @param $block
   */
  public function setContextualFilters($block) {
    $config = $block->getConfiguration();

    if (!empty($config['views_block_overrides']['contextual_filter']['contextual_filter'])) {
      foreach ($config['views_block_overrides']['contextual_filter']['contextual_filter'] as $id => $values) {

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

    $handler = $this->getView()->display_handler->getHandler('argument', $id);
    if (isset($handler->options['validate']['type'])) {
      $options = $handler->options;
      $validate_type = $options['validate']['type'];
    }
    return $validate_type != "none" ? $validate_type : $default;
  }

  /**
   * Returns the multi value separator.
   *
   * @return string
   *   The separator character
   */
  public function getMultiValueSeparator() {
    return '+';
  }

}
