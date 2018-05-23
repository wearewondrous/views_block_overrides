<?php

namespace Drupal\views_block_overrides\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Views block configuration plugin plugin manager.
 */
class BlockSettingsPluginManager extends DefaultPluginManager {


  /**
   * Constructs a new BlockSettingsPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/BlockSettings', $namespaces, $module_handler, 'Drupal\views_block_overrides\Plugin\BlockSettingsPluginInterface', 'Drupal\views_block_overrides\Annotation\BlockSettings');

    $this->alterInfo('views_block_overrides_block_settings_info');
    $this->setCacheBackend($cache_backend, 'views_block_overrides_block_settings');
  }
}
