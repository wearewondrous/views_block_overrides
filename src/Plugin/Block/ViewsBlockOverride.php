<?php

namespace Drupal\views_block_overrides\Plugin\Block;

use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\views\ViewExecutableFactory;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Class ViewsBlockOverride
 *
 * Extends the ViewsBlock and injects the block instance.
 */
class ViewsBlockOverride extends ViewsBlock {
  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ViewExecutableFactory $executable_factory, EntityStorageInterface $storage, AccountInterface $user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $executable_factory,  $storage,  $user);
    $this->view->views_block_overrides['block_instance'] = $this;
  }
}
