# Views block override
 
Provides a new views block display (Block overrides) on top of ctools_block to allow custom block settings.

## Requirements

* Latest dev release of Drupal 8.x.
* [Chaos tool set](https://drupal.org/project/ctools)

## Configuration

Create a new block display type *Block overrides* or edit your existing view YAML configuration and change the display type to  `display_plugin: views_block_overrides`

- Enable ctool_block [Chaos tool set](https://drupal.org/project/ctools).
- Go to views and add a new display, type of "Block overrides" to your view.
- Check the Block overrides settings for enabling or disabling, reordering the custom block settings.
- Additional settings can be added by implementing new @BlockSettings plugins


## Things you can do

FYI, most of the features listed were tested with paragraph + block_field only (TODO: test the block settings page from the block layouts)

- enable or disable, also sort the `block settings` form the Views display > Block Overrides > Settings
- sorting the `block settings` will change the order of the form inputs on the block settings
- configure the `block settings` on the view under Views display > Block settigs, e.g. Entity Reference
- add `block settings` per block instance, see the additinal inputs on the block setting page or on the node edit page via [Block field](https://drupal.org/project/block_field) widget
- use the ContextualFilter block settings option to override the contextual filters
  - by default you'll see a text field for entering the custom values
  - by adding `validation criteria` to the conextual filter you will see a select list using entity selection (based on the Validation option selected)
  
## Technical details

### Plugin type @BlockSettings

Features supported by the plugin:

- Auto displays the new plugin implementations under the Views Allow settings

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
  - the ::renderArea() returns a basic render array displaying template suggestions
  
### Block Settings visibility  
  
- The block object can be accessed from the view by `view->views_block_overrides['block_instance']` see Drupal\views_block_overrides\Plugin\Block\ViewsBlockOverride::__construct()
  
## Plugin types (experimental)

#### Plugin type Contextual Filter 
  - Generates a text input or select for all the contextual filters.
  - The select input will appear if validation is applied on the contextual filter (e.g. Content > Page)
  
#### Plugin type Dynamic Format
  - Lets override the Views Format View mode.
  - Works together with the `Content (with dynamic view mode)` view format, choosed on Views > Format > Show.

#### Plugin type Inline entity
  - Lets to attach and inline edit entities on the block settings form
  - Allowes to setup the entity type / bundle on the View > Block Settings > Inline Entity
  - Renders the attached entity using the Views Area handler `Views block overrides - Inline entity`, see the area settings to choose the entity view mode.
  
#### Plugin type Headline
  - Generates Title, subtitle and link inputs for the block instance settings and as global settings on the views as well.

#### Plugin type Entity reference
  - Generates an entity reference input.
