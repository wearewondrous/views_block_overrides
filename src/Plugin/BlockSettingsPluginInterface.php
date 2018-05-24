<?php

namespace Drupal\views_block_overrides\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Views block configuration plugin plugins.
 */
interface BlockSettingsPluginInterface extends PluginInspectionInterface {

  /**
   * Return the name of the reusable form plugin.
   *
   * @return string
   */
  public function getTitle();

  /**
   * Checks if the plugin uses the area plugin.
   *
   * @return bool
   */
  public function areaEnabled();

  /**
   * {@inheritdoc}
   */
  public function defineOptions();

  /**
   * Provide the summary for page options in the views UI.
   *
   * This output is returned as an array.
   */
  public function optionsSummary(&$categories, &$options);

  /**
   * Provide the default form for setting options.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state);

  /**
   * Perform any necessary changes to the form values prior to storage.
   * There is no need for this function to actually store the data.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state);

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
  public function blockSettings(array $settings);

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
  public function blockForm(ViewsBlock $block, array $form, FormStateInterface $form_state);

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
  public function blockSubmit(ViewsBlock $block, $form, FormStateInterface $form_state);

  /**
   * Allows to change the display settings right before executing the block.
   *
   * @param \Drupal\views\Plugin\Block\ViewsBlock $block
   *   The block plugin for views displays.
   */
  public function preBlockBuild(ViewsBlock $block);


  /**
   * Builds the area options.
   *
   * @param array $options
   *   The modified options array.
   * @param array $context
   *   The context array containing the area object.
   *
   * @return array $options
   *   The modified options array.
   */
  public function defineAreaOptions(array $context);

  /**
   * Builds the area options form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param array $context
   *   The context array.
   */
  public function buildAreaOptionsForm(array &$form, FormStateInterface $form_state, array $context);

  /**
   * Builds the area render array.
   *
   * @param $context
   *   The context array containing the area object.
   *
   * @return array
   *   The render array.
   */
  public function renderArea(array $context);

  /**
   * Gets the block instance settings.
   */
  public function getBlockSettings();

  /**
   * Get's the block instance object.
   */
  public function getBlockInstance();

  /**
   * Checks if the config plugin is allowed form the views settings.
   *
   * @return bool
   *   TRUE if it's allowed
   */
  public function isAllowed();
}
