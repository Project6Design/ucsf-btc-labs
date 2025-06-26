# Role Assign

## Contents of this file

- Introduction
- Background
- Requirements
- Installation
- Configuration
- Usage
- Usage Notes
- Maintainers

## Introduction

RoleAssign specifically allows site administrators to further delegate the task
of managing user's roles while withholding the _Administer permissions_
permission.

RoleAssign introduces a new permission called _Assign roles_. Users with this
permission are able to assign selected roles to still other users. Only users
with the _Administer permissions_ permission may select which roles are
available for assignment through this module.

RoleAssign is ideal for smaller sites with a system administrator and one
assistant administrator role that should be reasonably restricted in what it
allows. For larger sites with multiple levels of administrators or whenever you
need finer-grained control over which role can assign which other role, check
out [Role Delegation](https://www.drupal.org/project/role_delegation).
See [#961682: Does the role delegation module supersede this module?](https://www.drupal.org/project/roleassign/issues/961682)
for a short discussion of the relative merits of the two modules.

This module was developed by TBarregren and is now maintained by salvis.
Drupal 7 port by salvis.
Drupal 8 port by svendecabooter and tkuldeep17.

## Background

It is possible for site administrators to delegate the user administration
through the _Administer users_ permission. But that doesn't include the right to
assign roles to users. That is necessary if the delegatee should be able to
administrate user accounts without intervention from a site administrator.

To delegate the assignment of roles, site administrators have had until now no
other choice than also grant the _Administer permissions_ permission. But that
is not advisable, since it gives right to access all roles, and worse, to grant
any rights to any role. That can be abused by the delegatee, who can assign
himself all rights and thereby take control over the site.

This module solves this dilemma by introducing the _Assign roles_ permission.
While editing a user's account information, a user with this permission will be
able to select roles for the user from a set of available roles. Roles available
are configured by users with the _Administer permissions_ permission.

CodeKarate has
a [nice introductory video](https://www.youtube.com/watch?v=U_DnhKbcpVc) showing
how to use RoleAssign.

## Requirements

This module requires no modules outside of Drupal core.

## Installation

Install as you would normally install a contributed Drupal module.
Visit https://www.drupal.org/node/1897420 for further information.

## Configuration

1. Log in as a Site Administrator.
2. Navigate to People > Permissions (/admin/people/permissions)
3. Grant the _Assign roles_ permission to roles that should be able to assign
   roles for other users
  - **Note:** These roles also must have the _Administer users_ permission
4. Navigate to People > Role assign (/admin/people/roleassign)
5. Select roles that should be available for to be assigned by users with the
   _Assign roles_ permission
6. Navigate to a user's edit page
7. Grant that user a role that has the _Assign roles_ and _Administer users_
   permissions
8. Repeat Step 6 to 7 for all applicable users

## Usage

1. Log in as a user with that has the _Assign roles_ and _Administer users_
   permissions
2. Navigate to a user's edit page
3. Review the Assignable roles and modify them, as necessary
4. Save the user

## Usage Notes

- The _Administer users_ permission is and remains a security-critical
  permission that must NOT be given to untrusted users!
- Granting the _Administer users_ permission to users will allow them to modify
  admin passwords or email addresses.
  The [User Protect](https://www.drupal.org/project/userprotect) module can help
  to prevent this. RoleAssign will protect user 1's name, email, and password
  fields, but it won't protect any other accounts.
- RoleAssign will keep your assistant admins within their limits, but if you
  introduce alternative ways to edit users, assign roles, or give permissions (
  like the "Administration: Users" view in the popular Administration Views
  module), then you may be opening up ways for your assistant admins to gain
  additional privileges.

## Maintainers

- Ankit Babbar - [webankit](https://www.drupal.org/u/webankit)
- Hans Salvisberg - [salvis](https://www.drupal.org/u/salvis)
- Jordan Thompson - [nord102](https://www.drupal.org/u/nord102)
- Joseph Olstad - [joseph.olstad](hhttps://www.drupal.org/u/josepholstad)
- Sven Decabooter - [svendecabooter](https://www.drupal.org/u/svendecabooter)
- Thomas Barregren - [TBarregren](https://www.drupal.org/u/tbarregren)
- Tobias Sjösten - [tobiassjosten](https://www.drupal.org/u/tobiassjosten)
