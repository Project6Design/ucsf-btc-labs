/**
 * @file
 * draggableviews.js
 *
 * Defines the behaviors needed for draggableviews integration.
 */

(function (Drupal, once) {
  Drupal.behaviors.draggableviewsWeights = {
    attach(context) {
      once(
        'draggableviews-init',
        'table[id^="draggableviews-table-"]',
        context,
      ).forEach((table) => {
        const updateWeights = () =>
          table.querySelectorAll('tbody tr').forEach((row, i) => {
            const input = row.querySelector('.draggableviews-weight');
            if (input) input.value = i;
          });

        // Set initial weights
        updateWeights();

        // Move drag handles to first visible column
        table.querySelectorAll('.tabledrag-handle').forEach((handle) => {
          const row = handle.closest('tr');
          const visibleCell = Array.from(row.querySelectorAll('td')).find(
            (cell) => window.getComputedStyle(cell).display !== 'none',
          );
          if (visibleCell && !visibleCell.contains(handle)) {
            visibleCell.insertBefore(handle, visibleCell.firstChild);
          }
        });

        // Update weights after drag-and-drop
        const tableDrag = Drupal.tableDrag[table.id];
        if (tableDrag) {
          const originalOnSwap = tableDrag.row.prototype.onSwap;
          tableDrag.row.prototype.onSwap = function (swappedRow) {
            if (originalOnSwap) originalOnSwap.call(this, swappedRow);
            updateWeights();
          };
        }
      });
    },
  };
})(Drupal, once);
