# AKAIV Agents Service

Cloudflare Workers Agents SDK service for document analysis. Each document UUID maps to a Durable Object instance, so analysis counters and future agent state remain isolated per document.

## Local development

```bash
npm install
npx wrangler secret put AGENT_SHARED_SECRET
npm run dev
```

The Laravel application calls `POST /api/analyze-document` with a bearer token. Configure the Laravel app with:

```dotenv
DOCUMENT_AGENT_URL=http://localhost:8787
DOCUMENT_AGENT_SECRET=the-same-value-used-by-wrangler
```

The Laravel bridge is `POST /documents/{document}/analyze` and requires the authenticated user to pass the document policy.

## Deployment

```bash
npm run typecheck
npm run deploy
```

Set `DOCUMENT_AGENT_URL` in Laravel to the deployed Worker URL and set `DOCUMENT_AGENT_SECRET` to the same secret stored in the Worker. Do not commit either secret.

The Agents SDK route is also available at `/agents/document-assistant/{document-uuid}` for SDK clients using the agent transport.
