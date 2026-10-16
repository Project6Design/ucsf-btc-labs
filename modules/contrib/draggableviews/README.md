# DraggableViews

DraggableViews makes rows of a view "draggable" which means that they can be rearranged by Drag'n'Drop. This allows content editors to manually control the order of items displayed in a view.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/draggableviews).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/draggableviews).


## Requirements

This module requires the following core modules:

* Views

## Installation

1. Install as you would normally install a contributed Drupal module. For further
   information, see
   [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

2. Enable the module at `/admin/modules` or using Drush: `drush en draggableviews`

## Permissions

To use DraggableViews, users need the following permissions:

* **Access draggableviews**: Allows users to see and use the drag-and-drop functionality
* **Access administration pages**: Required to access the Views UI

Configure permissions at `/admin/people/permissions`.

## Configuration

### Basic Setup

1. **Enable the module**
  * Go to `/admin/modules` and enable DraggableViews

2. **Create a new view**
  * Navigate to `/admin/structure/views/add`
  * Select the entity type you want to make draggable (e.g., Content, Users, etc.)
  * Check "Create a page" (to display your sorted content)
  * Check "Create a block" (this will contain the drag-and-drop interface)
  * Click "Continue & edit"

3. **Configure the page display**
  * Set the display format as desired (table, list, grid, etc.)
  * Add any fields you want to display
  * Under "SORT CRITERIA", add "Draggableviews: Weight" and set to ascending
  * Save the view

4. **Configure the block display**
  * Switch to the block display
  * Set the display format to "Table" (required for drag-and-drop functionality)
  * Ensure you have at least one field (like "Content: Title")
  * Add the "Draggableviews: Content" field:
    * Click "Add" in the FIELDS section
    * **Important**: Change "For: 'All displays'" to "This block (override)"
    * Select "Draggableviews: Content" and apply
  * Under "SORT CRITERIA", ensure "Draggableviews: Weight" is set to ascending
  * Remove any other sort criteria or ensure they come after the Draggableviews weight
  * Save the view

5. **Place the block**
  * Go to `/admin/structure/block`
  * Place your newly created draggable block in a region
  * Set visibility settings as needed
  * Save the block configuration

6. **Verify configuration**
  * Go to your page display
  * Go to your block display
  * Drag items to reorder them
  * Verify that the order is reflected on the page display

### Advanced Configuration

#### Storage Options

DraggableViews supports multiple storage methods:

* **Field Storage**: Store order in a dedicated field (recommended for most use cases)
* **Database Table**: Store order in a separate database table (default)

To change the storage method:
1. Edit your view
2. Click on "Draggableviews: Weight" under SORT CRITERIA
3. Select your preferred storage method
4. Save the view

#### Using with Different Entity Types

DraggableViews works with any entity type in Drupal, not just content, such as:

* Users
* Taxonomy terms
* Custom entities
* Media
* Commerce products

The setup process is the same, just select the appropriate entity type when creating your view.

#### Multiple Ordered Views

You can have multiple ordered views of the same content by:

1. Creating a new view with draggable functionality
2. When configuring "Draggableviews: Weight", set a unique "Order ID" in the settings

This allows you to maintain different orderings for different contexts.

## Troubleshooting

### Common Issues

1. **Items cannot be dragged**
  * Ensure the block display format is set to "Table"
  * Verify the "Draggableviews: Content" field is added to the block display only
  * Check that user has "Access draggableviews" permission

2. **Order not saving**
  * Verify "Draggableviews: Weight" is in the SORT CRITERIA
  * Check that it's set to ascending order
  * Ensure it's the primary (first) sort criterion

3. **Order not displaying on page**
  * Confirm the page display has "Draggableviews: Weight" as a sort criterion
  * Verify the sort is set to the correct view/display in the settings

4. **AJAX errors when dragging**
  * Check browser console for specific errors
  * Verify Drupal core and the module are up to date
  * Try disabling conflicting JavaScript modules

5. **Performance issues with large datasets**
  * Add appropriate filters to limit the number of items
  * Consider using pagination on the draggable view

## Additional Resources

* [Video tutorial](https://www.drupal.org/docs/contributed-modules/draggableviews/video-tutorials)
* [DraggableViews API documentation](https://www.drupal.org/docs/contributed-modules/draggableviews/api)
* Demo module (included as a submodule in `/modules/draggableviews_demo`)

<!-- cspell:disable -->
## Maintainers

- Tyler Struyk - [iStryker](https://www.drupal.org/u/istryker)
- Martin Anderson-Clutz [mandclu](https://www.drupal.org/u/mandclu)
- Andrii Podanenko - [podarok](https://www.drupal.org/u/podarok)
<!-- cspell:enable -->