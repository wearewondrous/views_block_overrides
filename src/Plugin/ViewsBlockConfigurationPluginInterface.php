<?php

namespace Drupal\views_block_overrides\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Views block configuration plugin plugins.
 */
interface ViewsBlockConfigurationPluginInterface extends PluginInspectionInterface {

  /**
   * Return the name of the reusable form plugin.
   *
   * @return string
   */
  public function getTitle();

  /**
   * {@inheritdoc}
   */
  public function defineOptions();

  /**
   * Provide the default form for setting options.
   */
  public function buildOptionsForm($form, FormStateInterface $form_state);

  /**
   * Provide the summary for page options in the views UI.
   *
   * This output is returned as an array.
   */
  public function optionsSummary($categories, $options);

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

}
