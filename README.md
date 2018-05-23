# Views block override
 
Provides a new views block display (Block overrides) on top of ctools_block to allow custom block settings.

## Requirements

* Latest dev release of Drupal 8.x.
* [Chaos tool set](https://drupal.org/project/ctools)

## Configuration

Module ships with a simple configuration UI which allows you to create, edit
and delete entity browsers. 

- Enable ctool_blocks [Chaos tool set](https://drupal.org/project/ctools).
- Go to views and add a new display, type of "Block overrides" to your view.
- Check the allow settings for enabling or disabling the custom settings.
- Additional settings can be added by implementing new @BlockSettings plugins


## Things you can do

- enable or disable the `block settings` form the Views display > Allow Settings
- configure the `block settings` on the view under Views display > Block settigs, e.g. Entity Reference
- add `block settings` per block instance, see the additinal inputs on the block setting page or on the node edit page via [Block field](https://drupal.org/project/block_field) widget
- use the ContextualFilter block settings option to override the contextual filters
  - by default you'll see a text field for entering the custom values
  - by adding `validation criteria` to the conextual filter you will see a select list using entity selection (based on the Validation option selected)
 

## Technical details

### Plugin type @BlockSettings

Features supported by the plugin:

- Auto display the new plugin implementations under the Views Allow settings

- Adds custom settings form on block instance, see  
  - `Drupal\views_block_overrides\Plugin\BlockSettingsPluginInterface::blockSettings()`
  - `Drupal\views_block_overrides\Plugin\BlockSettingsPluginInterface::blockForm()`
  - `Drupal\views_block_overrides\Plugin\BlockSettingsPluginInterface::blockSubmit()`

- Adds custom settings per plugin on the view display, see  
  - `Drupal\views_block_overrides\Plugin\BlockSettingsPluginInterface::optionsSummary()`
  - `Drupal\views_block_overrides\Plugin\BlockSettingsPluginInterface::buildOptionsForm()`
  - `Drupal\views_block_overrides\Plugin\BlockSettingsPluginInterface::submitOptionsForm()`  

- Automatically creates a Views Area handler with optional settings for each plugins having the settings "area = true", see   - `Drupal\views_block_overrides\Plugin\BlockSettingsPluginInterface::defineAreaOptions()`
  - `Drupal\views_block_overrides\Plugin\BlockSettingsPluginInterface::buildAreaOptionsForm()`
  - `Drupal\views_block_overrides\Plugin\BlockSettingsPluginInterface::renderArea()`

### Theme the Views Area handler 
  - the Views Area handler requires to implement the corresponding theme
  - the ::renderArea() returns a render array suggesting the #theme [VIEWS_CURRENT_DISPLAY]_[AREA_ID]
  
### Block Settings visibility  
  
- All the block object can be accessed from the view by `view->views_block_overrides['block_instance']` see Drupal\views_block_overrides\Plugin\Block\ViewsBlockOverride::__construct()
- 
  
   
