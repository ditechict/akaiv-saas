import { Agent, routeAgentRequest } from "agents";

export interface Env {
  DOCUMENT_ASSISTANT: DurableObjectNamespace;
  AGENT_SHARED_SECRET: string;
  ALLOWED_ORIGIN?: string;
}

type DocumentPayload = {
  uuid: string;
  friendly_name: string;
  original_filename: string;
  description?: string | null;
  extracted_text?: string | null;
  mime_type?: string | null;
};

type AgentState = {
  documentUuid: string;
  analyses: number;
  lastAnalysisAt?: string;
};

export class DocumentAssistant extends Agent<Env, AgentState> {
  initialState: AgentState = {
    documentUuid: "",
    analyses: 0,
  };

  async onRequest(request: Request): Promise<Response> {
    if (request.method !== "POST" || new URL(request.url).pathname !== "/analyze") {
      return Response.json({ error: "Not found" }, { status: 404 });
    }

    const payload = (await request.json()) as DocumentPayload;
    if (!payload.uuid || !payload.friendly_name) {
      return Response.json({ error: "uuid and friendly_name are required" }, { status: 422 });
    }

    const text = (payload.extracted_text ?? payload.description ?? "").trim();
    const summary = text.length > 500 ? `${text.slice(0, 500).trim()}...` : text;
    const nextState: AgentState = {
      documentUuid: payload.uuid,
      analyses: this.state.analyses + 1,
      lastAnalysisAt: new Date().toISOString(),
    };
    this.setState(nextState);

    return Response.json({
      document: {
        uuid: payload.uuid,
        name: payload.friendly_name,
        filename: payload.original_filename,
        mimeType: payload.mime_type ?? null,
      },
      summary: summary || "No extracted text is available for this document.",
      analysisCount: nextState.analyses,
      analyzedAt: nextState.lastAnalysisAt,
    });
  }
}

function corsHeaders(request: Request, env: Env): Headers {
  const origin = request.headers.get("Origin");
  const allowedOrigin = env.ALLOWED_ORIGIN ?? "";
  const headers = new Headers({ "Access-Control-Allow-Headers": "Authorization, Content-Type", "Access-Control-Allow-Methods": "POST, OPTIONS", "Vary": "Origin" });
  if (origin && origin === allowedOrigin) headers.set("Access-Control-Allow-Origin", origin);
  return headers;
}

export default {
  async fetch(request: Request, env: Env): Promise<Response> {
    const headers = corsHeaders(request, env);
    if (request.method === "OPTIONS") return new Response(null, { status: 204, headers });

    const routed = await routeAgentRequest(request, env);
    if (routed) return routed;

    if (new URL(request.url).pathname !== "/api/analyze-document" || request.method !== "POST") {
      return Response.json({ error: "Not found" }, { status: 404, headers });
    }

    if (request.headers.get("Authorization") !== `Bearer ${env.AGENT_SHARED_SECRET}`) {
      return Response.json({ error: "Unauthorized" }, { status: 401, headers });
    }

    const payload = (await request.json()) as DocumentPayload;
    if (!payload.uuid) return Response.json({ error: "uuid is required" }, { status: 422, headers });
    const id = env.DOCUMENT_ASSISTANT.idFromName(payload.uuid);
    const agent = env.DOCUMENT_ASSISTANT.get(id);
    const response = await agent.fetch(new Request(`${new URL(request.url).origin}/analyze`, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(payload) }));
    const body = await response.text();
    headers.set("Content-Type", "application/json");
    return new Response(body, { status: response.status, headers });
  },
};
