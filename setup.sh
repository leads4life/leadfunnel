#!/usr/bin/env bash

# Prepare the writable lead-store used by submit_lead.php.  Set LEADS_FILE to
# choose a different location; the production default stays outside the web root.
set -euo pipefail

leads_file="${LEADS_FILE:-/var/www/leads_store/leads_data.json}"
leads_dir="$(dirname "$leads_file")"

install -d -m 0770 "$leads_dir"
install -m 0660 /dev/null "$leads_file"

printf 'Lead storage is ready at %s\n' "$leads_file"
