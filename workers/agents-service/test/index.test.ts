import { SELF } from "cloudflare:test";
import { describe, expect, it } from "vitest";

const endpoint = "https://example.com/api/analyze-document";

function post(body: unknown, token?: string): Promise<Response> {
  return SELF.fetch(endpoint, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: JSON.stringify(body),
  });
}

describe("analyze-document endpoint", () => {
  it("rejects requests without an authorization token", async () => {
    const response = await post({ uuid: "abc", friendly_name: "Doc" });
    expect(response.status).toBe(401);
  });

  it("rejects requests with an invalid token", async () => {
    const response = await post({ uuid: "abc", friendly_name: "Doc" }, "wrong-secret");
    expect(response.status).toBe(401);
  });

  it("returns 422 when uuid is missing", async () => {
    const response = await post({ friendly_name: "Doc" }, "test-secret");
    expect(response.status).toBe(422);
  });

  it("returns an analysis for an authorized request", async () => {
    const response = await post(
      { uuid: "doc-1", friendly_name: "Judgment", original_filename: "judgment.pdf", extracted_text: "Hello court" },
      "test-secret",
    );

    expect(response.status).toBe(200);
    const body = (await response.json()) as { summary: string; document: { uuid: string } };
    expect(body.document.uuid).toBe("doc-1");
    expect(body.summary).toContain("Hello court");
  });

  it("persists analysis state per document", async () => {
    await post({ uuid: "doc-2", friendly_name: "Doc", extracted_text: "one" }, "test-secret");
    const second = await post({ uuid: "doc-2", friendly_name: "Doc", extracted_text: "two" }, "test-secret");

    const body = (await second.json()) as { analysisCount: number };
    expect(body.analysisCount).toBeGreaterThanOrEqual(2);
  });

  it("answers CORS preflight with 204", async () => {
    const response = await SELF.fetch(endpoint, {
      method: "OPTIONS",
      headers: { Origin: "https://akaiv.localhost" },
    });

    expect(response.status).toBe(204);
    expect(response.headers.get("Access-Control-Allow-Origin")).toBe("https://akaiv.localhost");
  });
});
