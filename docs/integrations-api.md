# Internal integrations API

All endpoints are internal and are prefixed with `/api/integrations`. Send the company-specific secret in `X-Integration-Token`. Tokens are stored only as SHA-256 hashes. The development seeder reads `INTEGRATION_DEVELOPMENT_TOKEN` and stores its hash; it never prints or returns the token.

Requests and responses use JSON. Authentication failures return `401` with a Portuguese message. A conversation outside the token's company returns `404`.

## `POST /whatsapp/inbound`

Body: `provider_message_id` (string), `whatsapp_number` (string), `contact_name` (nullable string), `body` (string), and `occurred_at` (ISO-8601 date). Creates or updates the contact, finds or creates its open conversation, and writes an inbound/customer message. Repeated `provider_message_id` values are idempotent. Returns `201` for a new event or `200` for a replay, with `conversation_id`, `automation_state`, and `human_paused_until`.

## `GET /conversations/{conversation}/context`

Returns `conversation_id`, automation state, pause time, `ai_summary`, and up to 12 chronological messages. Every message contains `body`, `direction`, `sender`, and `occurred_at`.

## `GET /parts/search?q={query}`

Returns up to three active in-stock parts matching name, brand, model, SKU, or aliases. Each `data` item has `name`, `model`, BRL-formatted `price`, and `available`.

## `POST /conversations/{conversation}/messages`

Body: `body` (string), `sender` (`bot` or `human`), and optional `provider_message_id`. Stores an outbound message. A repeated non-null provider ID is idempotent and returns `200`; a new message returns `201` with `message_id` and `conversation_id`.

## `POST /conversations/{conversation}/handoff`

No body. Sets `automation_state` to `human_paused` and `human_paused_until` to exactly two hours from the request time.

## `POST /conversations/{conversation}/summary`

Body: `ai_summary`, nullable string limited to 5,000 characters. Updates only the conversation in the authenticated company.
