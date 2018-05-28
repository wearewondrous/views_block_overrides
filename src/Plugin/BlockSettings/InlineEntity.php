<?php

namespace Drupal\views_block_overrides\Plugin\BlockSettings;

use Drupal\views_block_overrides\Plugin\BlockSettingsPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\Component\Utility\NestedArray;

/**
 * A views block configuration plugin that allows to pass exposed filters as
 * block configuration configuration.
 *
 * @BlockSettings(
 *   id = "inline_entity",
 *   title = @Translation("Inline entity"),
 *   area = true
 * )
 */
class InlineEntity extends BlockSettingsPluginBase {

  use EntityTypeBundleSelectionTrait;

  /**
   * {@inheritdoc}
   */
  public function blockSettings() {
    $settings = parent::blockSettings();
    $settings['inline_entity'] = NULL;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm(ViewsBlock $block, array $form, FormStateInterface $form_state) {
    $subform = parent::blockForm($block, $form, $form_state);
    $block_configuration = $this->getBlockSettings();
    $settings = $this->configuration['view_display']->getOption($this->pluginId);
    $entity = NULL;
    $entity_type = $settings['entity_reference']['target_type'];
    $bundle = $settings['entity_reference']['bundle_type'];
    if (isset($block_configuration['inline_entity']['target_entity'])) {
      $target_entity_id = $block_configuration['inline_entity']['target_entity'];
      if ($entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($target_entity_id)) {
        $entity = $bundle == $entity->bundle() ? $entity : NULL;
      }
    }

    $subform['inline_entity'] = [
      '#type' => 'details',
      '#title' => $this->getCustomLabel(),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form_mode = 'edit';
    $subform['inline_entity']['target_entity'] = [
      '#type' => 'inline_entity_form',
      '#entity_type' => $entity_type,
      '#bundle' => $bundle,
      '#form_mode' => $form_mode,
      // If the #default_value is NULL, a new entity will be created.
      '#default_value' => $entity,
    ];

    return $subform;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit(ViewsBlock $block, $form, FormStateInterface $form_state) {
    $parents = array_merge($form['#array_parents'], [
      $this->getViewDisplay()->getPluginId(),
      $this->getPluginId(),
      'inline_entity',
      'target_entity',
    ]);
    $element = NestedArray::getValue($form_state->getCompleteForm(), $parents);
    $entity_id = NULL;
    $triggering_element = $form_state->getTriggeringElement();
    $save = $triggering_element['#type'] == 'submit' && !isset($triggering_element['#ajax']) && $element['#entity']->isNew();
    if (isset($element['#entity'])) {
      $entity = $element['#entity'];
      if ($save) {
        $status = $entity->save();
      }
      $entity_id = $entity->id();
    }
    $values['inline_entity']['target_entity'] = $entity_id;
    return $values;
  }

  /**
   * Provide the default form for setting options.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
   $subform = $this->buildSelectionSettingsForm($form, $form_state);
   return $subform;
  }

  /**
   * Entity reference Ajax callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The properties element.
   */
  public static function entityReferenceAjaxCallback(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    return $element;
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
   * {@inheritdoc}
   */
  public function defineAreaOptions(array $context) {
    $options['view_mode'] = ['default' => 'default'];
    return $options;
  }

  /**
   * {@inheritdoc
   */
  public function buildAreaOptionsForm(array &$form, FormStateInterface $form_state, array $context) {
    $area_options = $context['area']->options;
    $display_options = $this->getDisplayOptions();
    $entity_type = $display_options['entity_reference']['target_type'];

    $options = \Drupal::entityManager()->getViewModeOptions($entity_type);

    // Build the setting form.
    $form['view_mode'] = [
      '#title' => $this->t('View Mode'),
      '#description' => $this->t('The view mode the entity will be rendered.'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $area_options['view_mode'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function renderArea(array $context) {
    if (!$this->isAllowed()) {
      return [];
    }
    $area_options = $context['area']->options;
    $view_mode = $area_options['view_mode'];
    $block_settings = $this->getBlockSettings();
    $display_options = $this->getDisplayOptions();
    // Get the entity type and bundle.
    $entity_type = $display_options['entity_reference']['target_type'];
    $bundle = $display_options['entity_reference']['bundle_type'];
    // Get the entity id.
    $target_entity_id = $block_settings['inline_entity']['target_entity'];
    $render = [];
    if ($entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($target_entity_id)) {
      // Get the entity build.
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
      $render = $view_builder->view($entity, $view_mode);
    }

    return $render;
  }

}
