<?php

namespace Drupal\views_block_overrides\Plugin\BlockSettings;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;

/**
 * Provides an 'entity_reference' trait.
 */
trait EntityReferenceTrait {

  /**
   * Provide the default form for setting options.
   */
  public function buildEntityReferenceSettingsForm(&$form, FormStateInterface $form_state) {
    $section = $form_state->get('section');
    switch ($section) {
      case $this->pluginId:
        $settings = $this->configuration['view_display']->getOption($this->pluginId);

        //        if ($values = $form_state->getValue($section)) {
        //          $target_type = (!empty($values['target_type'])) ? $values['target_type'] : 'node';
        //          $selection_handler = 'default:' . $target_type;
        //          $selection_settings = [];
        //        }
        //        else
        if (($user_input = $form_state->getUserInput()) && isset($user_input[$this->pluginId])) {
          $target_type = (!empty($user_input[$this->pluginId]['target_type'])) ? $user_input[$this->pluginId]['target_type'] : 'node';
          $selection_handler = (!empty($user_input[$this->pluginId]['selection_handler'])) ? $user_input[$this->pluginId]['selection_handler'] : 'default:' . $target_type;
          $selection_settings = (!empty($user_input['selection_settings'])) ? $user_input['selection_settings'] : [];;
        }
        else {
          $target_type = (!empty($settings['target_type'])) ? $settings['target_type'] : 'node';
          $selection_handler = (!empty($settings['selection_handler'])) ? $settings['selection_handler'] : 'default:' . $target_type;
          $selection_settings = (!empty($settings['selection_settings'])) ? $settings['selection_settings'] : [];
        }

        // If the default selection handler has changed when need to update its
        // value.
        if (strpos($selection_handler, 'default:') === 0 && $selection_handler != "default:$target_type") {
          $selection_handler = "default:$target_type";
          $selection_settings = [];
          NestedArray::setValue($form_state->getUserInput(), [$this->pluginId, 'selection_handler'], $selection_handler);
          NestedArray::setValue($form_state->getUserInput(), [$this->pluginId, 'selection_settings'], $selection_settings);
        }

        // Set 'User' entity reference selection filter type role's #default_value
        // to an array and not NULL, which throws
        // "Warning: Invalid argument supplied for foreach()
        // in Drupal\Core\Render\Element\Checkboxes::valueCallback()"
        // @see \Drupal\user\Plugin\EntityReferenceSelection\UserSelection::buildConfigurationForm
        if ($target_type == 'user'
          && isset($selection_settings['filter']['type'])
          && $selection_settings['filter']['type'] == 'role'
          && empty($selection_settings['filter']['role'])) {
          $selection_settings['filter']['role'] = [];
        }

        /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $entity_reference_selection_manager */
        $entity_reference_selection_manager = \Drupal::service('plugin.manager.entity_reference_selection');

        // @see \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem
        $selection_plugins = $entity_reference_selection_manager->getSelectionGroups($target_type);
        $handlers_options = [];
        foreach (array_keys($selection_plugins) as $selection_group_id) {
          if (array_key_exists($selection_group_id, $selection_plugins[$selection_group_id])) {
            $handlers_options[$selection_group_id] = Html::escape($selection_plugins[$selection_group_id][$selection_group_id]['label']);
          }
          elseif (array_key_exists($selection_group_id . ':' . $target_type, $selection_plugins[$selection_group_id])) {
            $selection_group_plugin = $selection_group_id . ':' . $target_type;
            $handlers_options[$selection_group_plugin] = Html::escape($selection_plugins[$selection_group_id][$selection_group_plugin]['base_plugin_label']);
          }
        }

        // Entity Reference fields are no longer supported to reference Paragraphs.
        // @see paragraphs_form_field_storage_config_edit_form_alter()
        $target_type_options = \Drupal::service('entity_type.repository')->getEntityTypeLabels(TRUE);
        unset($target_type_options[(string) $this->t('Content')]['paragraph']);

        $subform['entity_reference'] = [
          '#type' => 'fieldset',
          '#title' => t('Entity reference settings'),
          '#weight' => -40,
          '#tree' => TRUE,
          '#prefix' => '<div id="entity-reference-selection-wrapper">',
          '#suffix' => '</div>',
        ];
        // Target type.
        $subform['entity_reference']['target_type'] = [
          '#type' => 'select',
          '#title' => $this->t('Type of item to reference'),
          '#options' => $target_type_options,
          '#required' => TRUE,
          '#empty_option' => t('- Select a target type -'),
          '#default_value' => $target_type,
          '#ajax' => [
            'callback' => [get_called_class(), 'entityReferenceAjaxCallback'],
            'wrapper' => 'entity-reference-selection-wrapper',
            'progress' => ['type' => 'fullscreen'],
          ],
        ];
        // Selection handler.
        $subform['entity_reference']['selection_handler'] = [
          '#type' => 'select',
          '#title' => $this->t('Reference method'),
          '#options' => $handlers_options,
          '#required' => TRUE,
          '#validated' => TRUE,
          '#value' => $selection_handler,
          '#default_value' => $selection_handler,
          '#ajax' => [
            'callback' => [get_called_class(), 'entityReferenceAjaxCallback'],
            'wrapper' => 'entity-reference-selection-wrapper',
            'progress' => ['type' => 'fullscreen'],
          ],
        ];
        // Selection settings.
        // Note: The below options are used to populate the #default_value for
        // selection settings.
        $entity_reference_selection_handler = $entity_reference_selection_manager->getInstance([
          'target_type' => $target_type,
          'handler' => $selection_handler,
          'handler_settings' => $selection_settings,
        ]);

        $subform['entity_reference']['selection_settings'] = $entity_reference_selection_handler->buildConfigurationForm([], $form_state);
        $subform['entity_reference']['selection_settings']['#tree'] = TRUE;

        // Remove the no-ajax submit button because we are not using the
        // EntityReferenceSelection with in Field API.
        unset(
          $subform['entity_reference']['selection_settings']['target_bundles_update']
        );

        // Remove auto create, except for entity_autocomplete.
        if ($this->getPluginId() != 'entity_autocomplete' || $target_type != 'taxonomy_term') {
          unset(
            $subform['entity_reference']['selection_settings']['auto_create'],
            $subform['entity_reference']['selection_settings']['auto_create_bundle']
          );
        }

        $custom_validation = [
          '#selection_settings' => $selection_settings,
          '#plugin_id' => $this->pluginId,
          '#element_validate' => [
            [get_class($this), 'validateSelection']
          ],
        ];

        $subform['entity_reference']['selection_settings']['target_bundles'] = array_merge_recursive($subform['entity_reference']['selection_settings']['target_bundles'], $custom_validation);
        $subform['entity_reference']['selection_settings']['sort']['field'] = array_merge_recursive($subform['entity_reference']['selection_settings']['sort']['field'], $custom_validation);;

        break;
    }
    $form[$this->pluginId] = $subform;
    $form_state->setCached(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public static function validateSelection(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $key = $form['#plugin_id'];
    if (isset($values[$key]['selection_settings']['target_bundles']) && empty($values[$key]['selection_settings']['target_bundles']) && isset($form['#selection_settings'])) {
      unset($values[$key]['selection_settings']['target_bundles']);
    }
    if (isset($values[$key]['selection_settings']['sort']['field']) && $values[$key]['selection_settings']['sort']['field'] == '_none') {
      unset($values[$key]['selection_settings']['sort']);
    }

    $form_state->setValues($values);
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

}
