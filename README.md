# Cheerful Agents lead funnel

This repository contains the extracted static site, its company pages, image
assets, and PHP endpoints for saving and viewing leads.

## Local setup

The lead endpoint writes to the path in `LEADS_FILE`. For local development,
keep that data out of version control and start PHP's built-in server with a
repository-local path:

```bash
LEADS_FILE="$PWD/.local/leads_data.json" ./setup.sh
LEADS_FILE="$PWD/.local/leads_data.json" php -S localhost:8000
```

Open `http://localhost:8000` to use the form. The PHP server process must use
the same `LEADS_FILE` value as the setup command.

## Production setup

By default, `submit_lead.php` stores leads in
`/var/www/leads_store/leads_data.json`, outside the web root. Run the setup
script as the account that should own the storage (or provide a writable
`LEADS_FILE` path) before serving the site:

```bash
./setup.sh
```

Set a strong value for the `$API_PASSWORD` in `submit_lead.php` before
deployment, and ensure only authorized staff can access `view_leads.php`.
