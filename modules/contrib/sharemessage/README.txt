
PROJECT
-------
http://drupal.org/project/sharemessage


INSTALLATION
------------
1. Download and extract the module to your sites/all/modules/contrib folder.
2. Enable the module on the Drupal Modules page (admin/modules) or using
   $ drush en

If you want to enable Sharrre plugin, do the following:
- Local library:
  1. Download the latest version of Sharrre at http://jster.net/library/sharrre.
  2. Put the minimized sharrre JS file in /libraries/sharrre and enable the
     libraries module.
- External library:
  1. Set the external URL for the library in Sharrre settings.


HOW TO ADD SHARING WIDGETS
--------------------------
1. Go to admin/config/services/sharemessage/sharemessage-settings and configure
   your default sharing options. Available options are documented in detail in
   the section "SETTINGS" below.
2. Open the "list" tab and click on "Add Share Message". Enter the values you
   want to be shared, note that you can use available tokens in order to make
   Share Messages display dynamic content.
3. If you don't want to use the default settings you configured on the settings
   page before, you can override those by checking "Override default settings".
4. Check the "Provide a block" setting.
5. Go to admin/structure/block and configure look for a block that has the name
   of your Share Message. Use the block settings to control where your share
   message is shown. (Mind the used tokens!).

Alternatively you can use an Entity Reference field on any node/entity with
display format "Rendered entity" to display Share Messages on within nodes and
fieldable entities.


ENFORCE OVERRIDING SHARE MESSAGES
---------------------------------
It is possible to enforce overriding of certain Share Messages if two Share
Messages point to the same URL.
This may be useful for example if you have a Share Message on a product page and
one on the checkout complete, while both point on the product page, you will
want to display another Share Message in case the user shares the checkout
complete message.

To accomplish this enforcement of the override, you can just enable the option
"Enforce the usage of this Share Message on the page it points to" on the Share
Message that is displayed on the checkout page.

Be careful if your site uses the querystring "?smid=" somewhere in another con-
text, this may lead to unexpected effects/conflicts. In that case, you can
disable this feature with a global setting.


OPTIONS
-------
- AddThis Profile ID:
  Optional. Enter your AddThis profile ID in order to be able to track your
  shares on your AddThis account.

- Default visible services:
  Determines which service buttons will be displayed on a share widget. You can
  override this per Share Message.
 
- Show additional services button:
  If checked, a button, which displays a list of additional services in a popup,
  will be displayed.
 
- Show AddThis counter:
  Adds a share counter that counts all shares and displays the amount of shares
  on a certain page.
 
- Default icon style:
  The size of the share buttons in pixel.

- Use local service definitions file:
  Enable this if your server is located behind some firewall that is blocking
  access to http://cache.addthiscdn.com/services/v1/sharing.en.json and the
  services select field is empty.

REQUIREMENTS
--------------------------------------------------------------------------------
- Social Share Privacy library:
  http://panzi.github.io/SocialSharePrivacy/

  Download minimized js from:
  http://panzi.github.io/SocialSharePrivacy/javascripts/jquery.socialshareprivacy.min.js
  copy file to:
  /libraries/socialshareprivacy/jquery.socialshareprivacy.min.js

  Download minimized css from:
  http://panzi.github.io/SocialSharePrivacy/stylesheets/jquery.socialshareprivacy.min.css
  copy file to:
  /libraries/socialshareprivacy/stylesheets/jquery.socialshareprivacy.min.css

  Download compressed files from:
  http://panzi.github.io/SocialSharePrivacy/
  extract files and copy content of images folder to:
  /libraries/socialshareprivacy/images


EXPORT/IMPORT
-------------
Using entities EntityAPIControllerExportable class, Share Messages are fully
export-/importable. In order to export a Share Message open the list
(admin/config/services/sharemessage) and use the "export" operation. Importing
can be done by following the "Import Share Message" link on the same page. Just
paste your exported sharemessage code and import it.


TESTING SHARES ON FACEBOOK
-------------
In order to test your shares on facebook, you can share your nodes or pages the
usual way, by clicking on the like button. This has the huge disadvantage, that
facebook may cache your share requests of the same page. To avoid this annoying
issue, use facebooks debugger tool: http://developers.facebook.com/tools/debug.


LOCAL SERVICES DEFINITION
-------------
When using the option "Use local service definitions file", the available
services might be outdated. If you see that they are outdated the services
definitions file, that is shipped with the module, has to be updated. Please
create a new issue for that at https://drupal.org/project/issues/sharemessage .


CREDITS
-------------
This module was developed and is maintained by MD Systems (by Miro Dietiker,
Berdir, s_leu). The Development has been sponsored by Kampaweb GmbH
http://kampaweb.ch/ and MD Systems http://www.md-systems.ch.
