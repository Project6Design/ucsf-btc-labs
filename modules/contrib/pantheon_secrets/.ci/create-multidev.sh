#!/bin/bash
#
# @file Create a Pantheon multidev, install this module into it, and force its PHP version.
#
# Runs on the GitHub Actions runner (ubuntu + setup-php), not inside a build container.
# Consolidates the previous create-multidev.sh + setup-drupal-repo.sh.
#
# Arguments:
#   $1 - MULTIDEV_NAME   (e.g. d11p85-42; truncated to 11 chars here)
#   $2 - TERMINUS_SITE   (fixture site machine name)
#   $3 - GITHUB_ENV file (to export MULTIDEV_ENV for later steps)
#   $4 - GIT_CONSTRAINT  (composer constraint for this module, e.g. dev-my-branch / 1.x-dev)
#   $5 - PHP_VERSION     (matrix PHP; written into the multidev pantheon.yml)
#   $6 - DRUPAL_VERSION  (selects the fixture base environment/branch)
#
# Requires: GITHUB_TOKEN (for composer github-oauth), SSH agent loaded (Pantheon git push).

set -euo pipefail

MULTIDEV_NAME="$1"
TERMINUS_SITE="$2"
GITHUB_ENV_FILE="${3:-${GITHUB_ENV:-}}"
GIT_CONSTRAINT="${4:-1.x-dev}"
PHP_VERSION="${5:-}"
DRUPAL_VERSION="${6:-}"

# Map the Drupal major to the fixture site's base environment/branch.
# Drupal 12 uses its own fixture site (MySQL 8.4) whose base is dev (branch master),
# because a MySQL 8.4 database can't be copied into a multidev on the MariaDB fixture site.
BASE_ENV=master
BASE_BRANCH=master
if [ "$DRUPAL_VERSION" = "10" ]; then BASE_ENV=drupal10; BASE_BRANCH=drupal10; fi
if [ "$DRUPAL_VERSION" = "11" ]; then BASE_ENV=drupal11; BASE_BRANCH=drupal11; fi
if [ "$DRUPAL_VERSION" = "12" ]; then BASE_ENV=dev; BASE_BRANCH=master; fi

# Pantheon multidev names are max 11 chars.
MULTIDEV="${MULTIDEV_NAME:0:11}"

# Delete a same-named multidev left over from a prior run.
if terminus multidev:list "$TERMINUS_SITE" --format=list | grep -q "^${MULTIDEV}$"; then
  terminus multidev:delete "$TERMINUS_SITE.$MULTIDEV" --delete-branch --yes
fi

# Create the multidev from the base environment for this Drupal major.
terminus multidev:create "$TERMINUS_SITE.$BASE_ENV" "$MULTIDEV"

# Clone the site repo at the base branch and create the multidev branch FROM it.
# Pantheon forks a new multidev's git branch from `master` (which can be stale),
# so we must base the pushed code on the per-Drupal-major branch instead, then
# force-push it onto the multidev branch below. Using `-b` (not a bare checkout)
# is what makes the per-major code win over the master fork.
GIT_URL=$(terminus connection:info "$TERMINUS_SITE.dev" --field=git_url)
GIT_SSH_COMMAND="ssh -o StrictHostKeyChecking=no" git clone "$GIT_URL" --branch "$BASE_BRANCH" pantheon-site
cd pantheon-site
git checkout -b "$MULTIDEV"

# Allow composer to read this module from GitHub. The runner's modern composer
# accepts the ghs_ Actions token (the old build container's composer did not).
composer config -g github-oauth.github.com "$GITHUB_TOKEN"
composer config repositories.secrets vcs https://github.com/pantheon-systems/pantheon_secrets.git

# Require this branch/tag of the module. The VCS repo resolves it from GitHub.
composer require "drupal/pantheon_secrets:${GIT_CONSTRAINT}" --ignore-platform-req=php

# Pantheon's git-based deploy rejects nested .git dirs; detect flat vs nested docroot.
rm -rf web/modules/contrib/pantheon_secrets/.git/ 2>/dev/null || true
rm -rf modules/contrib/pantheon_secrets/.git/ 2>/dev/null || true

# Set PHP version in pantheon.yml if specified.
# Without this, tests run under the base site's default PHP, not the matrix PHP.
# (Same approach as the other standardized Drupal modules, e.g. content-publisher.)
if [ -n "$PHP_VERSION" ]; then
  echo "Setting PHP version to ${PHP_VERSION}..."
  if [ -f pantheon.yml ]; then
    if grep -q "php_version:" pantheon.yml; then
      sed -i "s/php_version:.*/php_version: ${PHP_VERSION}/" pantheon.yml
    else
      echo "php_version: ${PHP_VERSION}" >> pantheon.yml
    fi
  else
    echo "api_version: 1" > pantheon.yml
    echo "php_version: ${PHP_VERSION}" >> pantheon.yml
  fi
fi

# Commit and push the build to the multidev branch; Pantheon Integrated Composer rebuilds.
git add .
git commit -m "CI build: pantheon_secrets (Drupal ${DRUPAL_VERSION}, PHP ${PHP_VERSION:-default})"
git push --set-upstream origin "$MULTIDEV" -f

cd ..

# Wait for Pantheon to finish building and deploying the pushed code.
# (Core terminus workflow:wait; build:workflow:wait was a build-tools plugin
# command bundled only in the deprecated quay build container.)
terminus workflow:wait "$TERMINUS_SITE.$MULTIDEV" --max=600

# Enable the module.
terminus drush "$TERMINUS_SITE.$MULTIDEV" -- en -y pantheon_secrets

# Save the multidev name for later steps.
echo "MULTIDEV_ENV=$MULTIDEV" >> "$GITHUB_ENV_FILE"
