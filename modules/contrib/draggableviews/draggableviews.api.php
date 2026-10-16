<?php

/**
 * @file
 * API documentation for DraggableViews hooks.
 */

/**
 * Alters the arguments passed to a Views query when using a join.
 *
 * This hook allows modules to modify the arguments used in a draggable views
 * join handler. It is invoked with the current view arguments and a context
 * array containing references to the select query, table, and view query.
 *
 * @param array $view_args
 *   The array of arguments passed to the Views query.
 * @param array $context
 *   Contextual information about the view and query with keys including:
 *   - select_query: The select query object.
 *   - table: The table being joined.
 *   - view_query: The view query object.
 *
 * @code
 *   function mymodule_draggableviews_join_withargs_alter(array &$view_args, array $context) {
 *     // Make all arguments lowercase.
 *     $view_args = array_map('strtolower', $view_args);
 *   }
 * @endcode
 */
function hook_draggableviews_join_withargs_alter(array &$view_args, array $context) : void {
  // Add your implementation here.
}

/**
 * Allows modules to react after a draggable view's sort order has been saved.
 *
 * This hook is invoked after the order of a draggable view is saved or reset.
 * Modules may use this to perform additional actions, such as logging or
 * updating related data.
 *
 * @param string $view_name
 *   The machine name of the view.
 * @param string $view_display
 *   The display ID of the view.
 *
 * @code
 *   function mymodule_draggableviews_sorted($view_name, $view_display) {
 *     // Custom logic after sort order is saved.
 *   }
 * @endcode
 */
function hook_draggableviews_sorted($view_name, $view_display) {
  // Add your implementation here.
}
