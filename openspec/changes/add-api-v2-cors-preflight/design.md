## Context

API v2 endpoints are PHP files called directly by clients. Most files set `Access-Control-Allow-Origin: *`, then validate the request method and parse the request body. Browser requests that include `Authorization` and `Content-Type: application/json` trigger a CORS preflight `OPTIONS` request before the real request.

Today those preflight requests are handled like normal endpoint calls, so method validation or body validation can return a non-2xx error. That prevents the browser from issuing the real request even when the API token and endpoint behavior are otherwise valid.

## Goals / Non-Goals

**Goals:**
- Allow browser preflight requests for API v2 endpoints.
- Return a 2xx preflight response without requiring an API token or request body.
- Include the requested CORS headers consistently.
- Preserve current endpoint behavior for real `GET`, `POST`, `PUT`, `PATCH`, and `DELETE` requests.
- Keep the implementation centralized enough that new API v2 endpoints can opt in with minimal boilerplate.

**Non-Goals:**
- Redesign API authentication or token authorization.
- Change API v1 CORS behavior unless shared helpers require compatible no-op behavior.
- Restrict allowed origins beyond the current wildcard behavior.
- Add route-level CORS configuration outside PHP unless the deployment needs it separately.

## Decisions

### Handle preflight before endpoint business validation

API v2 endpoints should call a shared preflight helper before `checkCorrectRequestMethod()` and before request-body parsing. The helper should detect `$_SERVER['REQUEST_METHOD'] === 'OPTIONS'`, emit the CORS headers, return `204 No Content` or another 2xx status, and terminate.

Alternative considered: let `checkCorrectRequestMethod()` accept `OPTIONS` for every endpoint. This still risks body parsing, auth, or endpoint-specific validation running after method validation, so an explicit early return is safer and easier to reason about.

### Centralize CORS header emission

Add shared utility functions for CORS headers and preflight handling instead of hand-writing headers in each endpoint. The helper should set:

- `Access-Control-Allow-Origin: *`
- `Access-Control-Allow-Methods: <endpoint method>, OPTIONS`
- `Access-Control-Allow-Headers: Authorization, Content-Type`
- `Access-Control-Max-Age: 86400` for preflight responses

Alternative considered: configure CORS in nginx or Apache. That can be valid operationally, but this codebase already sets API CORS headers in PHP, and the change must travel with the application code.

### Preserve real response CORS

Real API v2 responses must continue to include `Access-Control-Allow-Origin: *`. Existing explicit headers may remain, or they can be replaced by a shared helper if behavior stays equivalent.

Alternative considered: only handle `OPTIONS` and leave real responses unchanged. This works for current files, but the spec should require real responses to keep the CORS header because browsers check it on the actual request too.

## Risks / Trade-offs

- Some API v2 files may forget to call the new helper -> Audit every file under `api/v2/` and add a task to cover all direct endpoints.
- Duplicate `Access-Control-Allow-Origin` headers may appear if both endpoint and helper emit it -> prefer replacing endpoint-local origin headers with the shared helper where files are touched.
- Preflight should not require authentication -> terminate before token/client checks and avoid leaking data by returning no body.
- Deployment-level CORS rules may conflict with PHP headers -> verify with curl against a representative endpoint after deployment.

## Migration Plan

1. Add the shared CORS/preflight utility.
2. Update API v2 endpoint files to call it before method and body validation.
3. Verify representative `OPTIONS` and real requests for `items/get.php` with both `GET` and compatibility `POST`, `items/create.php`, and one binary/data endpoint.
4. Rollback by reverting the helper calls and shared helper if browser preflight behavior needs to return to the previous state.
