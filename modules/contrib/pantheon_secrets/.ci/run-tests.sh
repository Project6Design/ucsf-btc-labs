#!/bin/bash
#
# @file Functional test: set a Pantheon secret, sync it to a Key entity, verify the roundtrip.
#
# Requires: TERMINUS_SITE, MULTIDEV_ENV environment variables.

set -eo pipefail

if [[ -z "$TERMINUS_SITE" || -z "$MULTIDEV_ENV" ]]; then
  echo "::error::TERMINUS_SITE and MULTIDEV_ENV must be set."
  exit 1
fi

SITE_ENV="${TERMINUS_SITE}.${MULTIDEV_ENV}"

echo "Generating random key name and value..."
RAND=$(date | shasum | fold -w 8 | head -n 1)
# Include Drupal + PHP in the name so concurrent matrix legs on the shared
# fixture site never collide on the same secret name.
PHP_SHORT=$(echo "${PHP_VERSION:-0}" | tr -d '.')
KEY_NAME="d${DRUPAL_VERSION}p${PHP_SHORT}_${GITHUB_RUN_NUMBER}_${RAND}"
KEY_VALUE=$(date | shasum | fold -w 40 | head -n 1)

# Do not echo KEY_VALUE: it would leak the secret value into CI logs.
echo "Key name: ${KEY_NAME}"

echo "Setting secret..."
terminus secret:set "${TERMINUS_SITE}" --scope=web,user "${KEY_NAME}" "${KEY_VALUE}"

echo "Syncing keys..."
terminus drush "${SITE_ENV}" -- pantheon-secrets:sync

echo "Retrieving key..."
VALUE=$(terminus drush "${SITE_ENV}" -- key:value-get "${KEY_NAME}" | awk 'NR==4 {print $0}')
# Trim leading AND trailing whitespace (drush renders the value in a padded table cell).
VALUE=$(echo "$VALUE" | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//')

echo "Checking key..."
if [ "$VALUE" != "$KEY_VALUE" ]; then
  echo "::error::Key value does not match the original secret value"
  terminus secret:delete "${TERMINUS_SITE}" "${KEY_NAME}" || true
  exit 1
fi
echo "Roundtrip OK: synced key value matches the original secret."

echo "Delete test secret..."
terminus secret:delete "${TERMINUS_SITE}" "${KEY_NAME}"
