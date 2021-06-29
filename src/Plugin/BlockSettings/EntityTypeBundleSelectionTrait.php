<?php

namespace Drupal\views_block_overrides\Plugin\BlockSettings;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\views\Plugin\Block\ViewsBlock;

trait EntityTypeBundleSelectionTrait {

  /**
   * Provide the default form for setting options.
   */
  public function buildSelectionSettingsForm(&$form, FormStateInterface $form_state) {
    $settings = $this->getOptionsFormSettings();

    if (($user_input = $form_state->getUserInput()) && isset($user_input[$this->pluginId])) {
      $target_type = (!empty($user_input[$this->pluginId]['entity_reference']['target_type'])) ? $user_input[$this->pluginId]['entity_reference']['target_type'] : 'node';
      $bundle_type = (!empty($user_input[$this->pluginId]['entity_reference']['bundle_type'])) ? $user_input[$this->pluginId]['entity_reference']['bundle_type'] : '';
    }
    else {
      $target_type = (!empty($settings['entity_reference']['target_type'])) ? $settings['entity_reference']['target_type'] : NULL;
      $bundle_type = (!empty($settings['entity_reference']['bundle_type'])) ? $settings['entity_reference']['bundle_type'] : NULL;
    }

    $target_type_options = $this->getEntityTypes();
    $bundle_options = $this->getBundles($target_type);

    // Reset the bundle type if the entity type has changed.
    if (!isset($bundle_options[$bundle_type])) {
      $bundle_type = NULL;
      NestedArray::setValue($form_state->getUserInput(), [$this->pluginId, 'entity_reference', 'bundle_type'], $bundle_type);
    }

    $subform['entity_reference'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Entity reference settings'),
      '#weight' => -40,
      '#prefix' => '<div id="entity-reference-selection-wrapper">',
      '#suffix' => '</div>',
    ];
    // Target type.
    $subform['entity_reference']['target_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type of item to reference'),
      '#options' => $target_type_options,
      '#required' => TRUE,
      '#empty_option' => $this->t('- Select a target type -'),
      '#default_value' => $target_type,
      '#ajax' => [
        'callback' => [get_called_class(), 'entityTypeAjaxCallback'],
        'wrapper' => 'entity-reference-selection-wrapper',
        'progress' => ['type' => 'fullscreen'],
      ],
    ];

    $subform['entity_reference']['bundle_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle'),
      '#options' => $bundle_options,
      '#required' => TRUE,
      '#empty_option' => $this->t('- Select a bundle type -'),
      '#default_value' => $bundle_type,
    ];

    $form_state->setCached(FALSE);
    return $subform;
  }

  /**
   * Returns the list of existing Content entity types.
   *
   * @return array
   *   List of Content entity types.
   */
  public function getEntityTypes() {
    $options = [];
    $definitions = \Drupal::entityTypeManager()->getDefinitions();
    foreach ($definitions as $entity_type_id => $definition) {
      if (!$definition instanceof ContentEntityType) {
        continue;
      }
      $options[$entity_type_id] = $definition->getLabel();
    }

    return $options;
  }

  /**
   * Returns the list of bundles for the given entity type.
   *
   * @param string $target_type
   *   The entity type.
   * @return array
   *   List of bundles.
   */
  public function getBundles($target_type) {
    $options = \Drupal::service("entity_type.bundle.info")
      ->getBundleInfo($target_type);

    array_walk(
      $options,
      function ($item, $key) use (&$options) {
        $options[$key] = $item['label'];
      }
    );

    return $options;
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
  public static function entityTypeAjaxCallback(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    return $element;
  }
}
