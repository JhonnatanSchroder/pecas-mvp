# Internal integrations API

All endpoints are internal and are prefixed with `/api/integrations`. Send the company-specific secret in `X-Integration-Token`. Tokens are stored only as SHA-256 hashes. The development seeder reads `INTEGRATION_DEVELOPMENT_TOKEN` and stores its hash; it never prints or returns the token.

## Local development token rotation

Use the following command with the explicit development company ID. It asks for confirmation, generates a new plaintext token, stores only its SHA-256 hash in `companies.integration_token_hash`, and writes the plaintext directly to `LARAVEL_INTEGRATION_TOKEN` in the existing local `infra/n8n/.env` file. The token is never printed.

```sh
php artisan integration:rotate-token 1 --n8n-env=infra/n8n/.env
```

The value written to `LARAVEL_INTEGRATION_TOKEN` is the exact plaintext used by n8n in `X-Integration-Token`; do not replace it with its hash. Restart only the n8n service after rotation so it reloads the local environment file.

For a non-interactive local or development terminal, add `--force`. The command refuses `--force` in production.

```sh
php artisan integration:rotate-token 1 --n8n-env=infra/n8n/.env --force
```

To diagnose a local n8n token without displaying it, verify the same explicit company ID against `infra/n8n/.env`:

```sh
php artisan integration:verify-token 1 --n8n-env=infra/n8n/.env
```

It prints only `TOKEN CONFERE` on a match. For a mismatch, missing company, unreadable file, or missing variable, it prints only `TOKEN NÃO CONFERE` and exits with a non-zero status.

Requests and responses use JSON. Authentication failures return `401` with a Portuguese message. A conversation outside the token's company returns `404`.

## `POST /whatsapp/inbound`

Body: `provider_message_id` (string), `whatsapp_number` (string), `contact_name` (nullable string), `body` (string), and `occurred_at` (ISO-8601 date). Creates or updates the contact, finds or creates its open conversation, and writes an inbound/customer message. Repeated `provider_message_id` values are idempotent. Returns `201` for a new event or `200` for a replay, with `conversation_id`, `automation_state`, and `human_paused_until`.

## `GET /conversations/{conversation}/context`

Returns `conversation_id`, automation state, pause time, `ai_summary`, and up to 12 chronological messages. Every message contains `body`, `direction`, `sender`, and `occurred_at`.

## `GET /parts/search?q={query}`

Returns up to three active in-stock parts matching name, brand, model, SKU, or aliases. Each `data` item has `name`, `model`, BRL-formatted `price`, and `available`.

## `POST /whatsapp/human-outbound`

Body: `provider_message_id` (string), `whatsapp_number` (string), `body` (string), and `occurred_at` (ISO-8601 date). Finds the customer's open conversation by WhatsApp number, writes an outbound/human message, and pauses automation for exactly two hours. Repeated `provider_message_id` values are idempotent. Returns `201` for a new event or `200` for a replay, with `message_id` and `conversation_id`. Returns `404` if there is no open conversation for the number in the token's company.

## `POST /conversations/{conversation}/messages`

Body: `body` (string), `sender` (`bot` or `human`), and optional `provider_message_id`. Stores an outbound message. A repeated non-null provider ID is idempotent and returns `200`; a new message returns `201` with `message_id` and `conversation_id`.

## `POST /conversations/{conversation}/handoff`

No body. Sets `automation_state` to `human_paused` and `human_paused_until` to exactly two hours from the request time.

## `POST /conversations/{conversation}/summary`

Body: `ai_summary`, nullable string limited to 5,000 characters. Updates only the conversation in the authenticated company.
