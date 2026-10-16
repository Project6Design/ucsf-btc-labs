# Draggableviews Demo

This module provides a demonstration of the [Draggableviews](https://www.drupal.org/project/draggableviews) module functionality. It sets up a content type and views that showcase how to implement drag-and-drop ordering of content in Drupal.

## Features

- Creates a "Draggableviews Demo" content type
- Sets up two view pages:
  - A display page that shows content in the specified order
  - An order page that allows drag-and-drop reordering of content

## Installation

1. Enable the module using Drush: `drush en draggableviews_demo`
2. Or enable it through the Drupal admin interface: `/admin/modules`

## Usage

1. Create several "Draggableviews Demo" content items at `/node/add/draggableviews_demo`
2. View the ordered content at `/draggableviews-demo`
3. Reorder the content using drag-and-drop at `/draggableviews-demo/order` (requires "access draggableviews" permission)
4. Return to the display page to see your content in the new order

## Creating New Content

To create new nodes of the draggableviews demo type:

1. Navigate to `/node/add/draggableviews_demo` or go to Content > Add content > Draggableviews Demo
2. Fill in the following fields:
   - **Title**: Enter a descriptive name for your content item (required)
   - **Body**: Add any optional description or content (optional)
3. Set publishing options as needed:
   - Published: Check to make the content visible
   - Promoted to front page: Check if you want the content on the front page
   - Sticky at top of lists: Check to keep the content at the top of lists
4. Click "Save" to create the content
5. Repeat to create multiple items that can be reordered

After creating several items, go to the order page (`/draggableviews-demo/order`) to arrange them in your preferred order.

## Testing the Reorder Functionality

To test the drag-and-drop reordering functionality:

1. Navigate to the order page at `/draggableviews-demo/order`
2. You'll see a list of your content items with drag handles (↕️) on the left side
3. To reorder:
   - Click and hold on the drag handle of an item
   - Drag it up or down to the desired position
   - Release to drop it in place
   - The new order is automatically saved (no need to click a save button)
4. To verify your changes:
   - Click on the "Display Page" link in the header text, or
   - Navigate to `/draggableviews-demo` to see your content in the new order

### Important Links

The demo includes several important links that help you navigate between views:

- **Order Page** (`/draggableviews-demo/order`): Where you can drag and drop to reorder content
- **Display Page** (`/draggableviews-demo`): Where you can view content in the ordered sequence
- **Context Menu**: Look for the "Order Draggableviews Demo" tab in the top right when viewing:
  - Individual content items of the Draggableviews Demo type
  - The display page
  - This tab provides quick access to the ordering interface

### Understanding the Context Menu

The context menu appears in the top right of the page when:

1. Viewing a Draggableviews Demo content item
2. Viewing the display page at `/draggableviews-demo`

This menu contains tabs that allow you to:
- Switch between viewing the content and reordering it
- Access the order page directly from any related content

This contextual navigation makes it easy to move between viewing and reordering operations without having to remember URLs.

## Permissions

To use this demo, users need:
- "Create Draggableviews Demo content" permission to create content
- "Access content" permission to view the display page
- "Access draggableviews" permission to access the ordering page

## How It Works

This demo illustrates how Draggableviews:
1. Stores ordering information in the database
2. Provides a drag-and-drop interface for reordering
3. Integrates with Views to display content in the specified order

You can examine the view configuration to understand how to implement similar functionality in your own projects.

## Customization

To customize this demo or create your own draggable views:
1. Clone the view at `/admin/structure/views/view/draggableviews_demo`
2. Modify the fields, filters, and display settings as needed
3. Ensure the "draggableviews" field is included in your order page
4. Configure the sort criteria to use the draggableviews weight field

## Troubleshooting

If the drag-and-drop functionality doesn't work:
- Ensure JavaScript is enabled in your browser
- Check that you have the required permissions
- Verify that the Draggableviews module is properly installed
