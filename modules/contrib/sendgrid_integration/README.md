SendGrid Integration for Drupal
--------------------------------------------------------------------------------
This project is not affiliated with SendGrid, Inc.

Use the issue tracker located at [Drupal.org](https://www.drupal.org/sendgrid_integration)
for bug reports or questions about this module. If you want more info about
SendGrid services, contact [SendGrid](https://sendgrid.com).

This module uses a wrapper library for the SendGrid API. At the moment the
wrapper library is for V2 of the API. V3 upgrade is being developed.

FUNCTIONALITY
--------------------------------------------------------------------------------
This module overrides default email sending behaviour and sending emails through
SendGrid Transactional Email service instead. Emails are sent via a web service
and does not function like SMTP therefore there are certain caveats with other
email formatting modules. Read below for details.

Failures to send are re-queued for sending later. Queue of failed messages are
run on a 60 minute interval.

REQUIREMENTS
--------------------------------------------------------------------------------
Mailsystem - A module to create an agnostic management layer for Mail. Very
useful for controlling the mail system on Drupal.

PHP dependencies for this module are loaded via Composer in Drupal 8.

INSTALLATION
--------------------------------------------------------------------------------
Before starting your installation, be aware that this module uses composer to
load dependencies. In Drupal 8, there are different ways to configure your site
to use [composer for contributed modules](https://www.drupal.org/node/2718229#managing-contributed).

As of recent changes with Drush for Drupal 8.4, there is no option to download
a module with Drush. All downloading of modules now resides with composer.

**Installation via command line and composer:**

1. Start at the root of your Drupal 8 installation and issue the command
   <code>composer require drupal/sendgrid_integration</code>.

2. The module will be downloaded from Drupal.org, the dependency API wrapper will
   be downloaded from Github, and your main composer.json will be updated.

3. Navigate to Modules and enable SendGrid Integration in the Mail category.

4. Configure your SendGrid API-Key in admin/config/services/sendgrid

5. Confirm that the mail system is setup to use Sendgrid for how you wish to run
   you website. If you want it all to run through Sendgrid then you set the
   System-wide default MailSystemInterface class to "SendGridMailSystem". As an
   example, see this [image](https://www.drupal.org/files/issues/sengrid-integration-mailsystem-settings-example.png).

* Composer Documentation: [https://getcomposer.org/doc/](https://getcomposer.org/doc/)

* We are going to update the D8 version of this module to allow for an optional
manual installation of the API wrapper.

HTML Email
--------------------------------------------------------------------------------
In order to send HTML email. Your installation of Drupal must generate an email
with the proper headers.

We recommend using the module [Mimemail](https://www.drupal.org/project/mimemail)
for HTML formatting of emails.

NOTE: Please note that SendGrid Integration also adds a "per module" setting for\
the mail formatter/sender below where the site default is defined. The formatter 
there needs to be changed to Mimemail.

OPTIONAL
--------------------------------------------------------------------------------
If sending email fails with certain (predefined) response codes the message be
added to Cron Queue for later delivery. In order for this to function, you must
configure Cron running period and when it is possible also add your drupal site
to crontab (Linux only), read more about cron at https://www.drupal.org/cron.

If you would like a record of the emails being sent by the website, installing
Maillog (https://www.drupal.org/project/maillog) will allow you to store local
copies of the emails sent. Sendgrid does not store the content of the email.

DEBUGGING
--------------------------------------------------------------------------------
Debugging this module while installed can be done by installing the Maillog
module (https://www.drupal.org/project/maillog). This module will allow you to
store the emails locally before they are sent and view the message generated
in the Sendgrid email object.

RESOURCES
--------------------------------------------------------------------------------
Information about the Sendgrid PHP Library is available on Github:
https://github.com/taz77/sendgrid-php-ng
