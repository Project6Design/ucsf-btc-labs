## Feature  statuses, states, and "importing"

The main Features administration page at **Administration > Configuration > Development > Features** (`/admin/config/development/features`) provides information on the status and state of available features and their configuration items.

## Feature  statuses

Possible statuses for a Feature are:

*   **Not exported**: the Feature has been detected but has not yet been exported (at least, to this site).
*   **Uninstalled**: the Feature is present in the site's file system but is not installed.
*   **Enabled**: the Feature is present and enabled on the site.

## Feature  and configuration states

Possible states of features or configuration include:

*   **Default**: configuration that is present in the Feature matches that currently on the site. If the Feature is regenerated, no change will be made to this configuration.
*   **Changed**: configuration that is present in the Feature is different from that on the site. If the Feature is regenerated, the version in the Feature will be overwritten by the version in the site's active configuration storage.
*   **Missing**: configuration that is present in the Feature is not available on the site. If the Feature is regenerated, that configuration will be removed. Missing configuration may simply reflect the fact that the Feature hasn't been enabled yet.
*   **New detected**: configuration present on the site but not already in the Feature has been automatically assigned to the Feature and will be added if the Feature is regenerated.
*   **Conflicts**: configuration assigned to or present in one Feature is the same as that assigned to or present in another feature. A conflict is not necessarily a problem, as it may mean simply that configuration that is currently provided by one Feature is set to be reassigned if the features are regenerated.

## Diffs

For each piece of configuration for which the version provided by the Feature doesn't match that in the site's active configuration storage, the differences are displayed in "diff" format (where - indicates a line that was removed and + indicates a line that was added). Admittedly, these changes can be fairly opaque

## "Importing" changes (formerly known as "reverting")

If the state of a Feature  is "Changed", you can click the `Changed` link to bring up a form where you can review changes and **import** updates. In previous versions of Features, this was referred to as "reverting" the Feature, in the sense that it was reverting the active configuration to the stored configuration.

Each configuration item is accompanied by a checkbox. To import the change from the Feature to the site, select the checkbox and click "Import changes".
