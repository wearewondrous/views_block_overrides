<?php

namespace Drupal\views_block_overrides\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Views block configuration plugin item annotation object.
 *
 * @see \Drupal\views_block_overrides\Plugin\BlockSettingsPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class BlockSettings extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The views display
   *
   * @var object
   */
  public $view_display;

  /**
   * The views object
   *
   * @var object
   */
  public $view;

  /**
   * Defines if the plugin uses the area plugin.
   *
   * @var bool
   */
  public $area;

}
