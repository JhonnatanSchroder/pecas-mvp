# n8n + WAHA WhatsApp

## Subir a stack local

1. Copie `.env.example` para `.env` dentro de `infra/n8n`.
2. Preencha todos os valores `change-this-*`. Obtenha o token de integração Laravel localmente; nunca o versione. Em Docker Desktop, `host.docker.internal` permite que o n8n acesse o Laravel executado no host.
3. Inicie os serviços com `docker compose --env-file .env up -d` a partir de `infra/n8n`.

WAHA fica exposto somente em `http://localhost:3000`. O n8n acessa-o pela rede Docker em `http://waha:3000`, configurado em `WAHA_API_URL`. Não use `localhost` nesta variável, pois o n8n é outro container.

O compose usa `devlikeapro/waha:latest-2026.9.2` com `WEBJS`. Esse engine baseado no WhatsApp Web é adequado para desenvolvimento local porque permite parear rapidamente por QR code; as sessões ficam no volume persistente `waha_sessions`.

## Criar e autenticar a sessão `default`

Com a stack em execução, crie a sessão (ela inicia automaticamente):

```sh
curl -X POST http://localhost:3000/api/sessions \
  -H "X-Api-Key: SUA_WAHA_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"name":"default"}'
```

Se ela estiver parada, inicie-a:

```sh
curl -X POST http://localhost:3000/api/sessions/default/start \
  -H "X-Api-Key: SUA_WAHA_API_KEY"
```

Abra `http://localhost:3000/dashboard`, autentique-se com as credenciais configuradas para WAHA e abra a sessão `default` para exibir o QR code. Alternativamente, obtenha a imagem do QR com `GET /api/default/auth/qr?format=image` e o cabeçalho `X-Api-Key`; escaneie-o em WhatsApp > Dispositivos conectados. Use sempre um número de desenvolvimento dedicado, nunca um número pessoal ou operacional.

## Testar `sendText`

Depois que a sessão estiver em `WORKING`, teste com um número de desenvolvimento (sem `+`, mantendo `@c.us`):

```sh
curl -X POST http://localhost:3000/api/sendText \
  -H "X-Api-Key: SUA_WAHA_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"session":"default","chatId":"5511999999999@c.us","text":"Teste WAHA"}'
```

## Importar o workflow no n8n

1. Importe `workflows/whatsapp-customer-service.json`. Ele permanece desativado após a importação.
2. Crie uma credencial **Header Auth** no n8n para WAHA com o cabeçalho `X-Api-Key` e o valor local de `WAHA_API_KEY`. Associe-a manualmente ao nó **Send through WAHA**.
3. Crie e associe a credencial OpenAI ao nó **OpenAI economical model**.
4. Mantenha preenchidas em `infra/n8n/.env`: `LARAVEL_INTEGRATIONS_URL`, `LARAVEL_INTEGRATION_TOKEN`, `WAHA_API_URL`, `WAHA_SESSION`, `WAHA_API_KEY` e `WAHA_WEBHOOK_SECRET`.
5. Ative o workflow somente após testar o webhook. WAHA envia os eventos `message` e `message.any` ao endereço interno `http://n8n:5678/webhook/waha`, autenticados com o cabeçalho `X-WAHA-Webhook-Secret`.

## Teste por fixture

Use `workflows/waha-webhook-fixture.json`. Envie cada objeto de `events` para a URL de teste do webhook, acrescentando `X-WAHA-Webhook-Secret` com o mesmo valor do `.env`. Os dois primeiros eventos comprovam deduplicação, o terceiro registra uma mensagem enviada pelo humano e pausa a conversa por duas horas, e o quarto é um eco `source=api` do bot que é ignorado. Não dependa de auto-mensagens WhatsApp para esse teste.

## Upgrade seguro do n8n

A imagem está fixada em `n8nio/n8n:2.43.6`. Antes de atualizar, mantenha o mesmo arquivo `.env`, especialmente `N8N_ENCRYPTION_KEY`, e faça um backup do volume `n8n_data` e do banco PostgreSQL. Não use `docker compose down -v`, pois esse comando remove volumes.

Com os backups concluídos, a partir de `infra/n8n`, execute somente:

```sh
docker compose --env-file .env pull n8n
docker compose --env-file .env up -d --no-deps n8n
docker compose --env-file .env logs -f n8n
```

Depois que as migrações terminarem e o healthcheck estiver saudável, abra o editor, confira as credenciais existentes e execute o workflow manualmente antes de ativá-lo.

## Acesso a variáveis pelo workflow local

Esta infraestrutura local define `N8N_BLOCK_ENV_ACCESS_IN_NODE=false` para que o nó de validação do webhook leia `WAHA_WEBHOOK_SECRET`. Essa configuração é aceitável somente neste ambiente de desenvolvimento atual. Antes de produção, substitua esse acesso por credenciais ou segredos gerenciados pelo n8n.
