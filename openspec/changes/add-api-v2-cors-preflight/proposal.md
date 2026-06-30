## Why

Browser clients cannot call API v2 endpoints that use the `Authorization` and `Content-Type` headers unless the server answers CORS preflight `OPTIONS` requests with a successful response and the required allow headers.

The API v2 files already set `Access-Control-Allow-Origin: *` for normal requests, but they do not consistently handle browser preflight requests before method validation and request-body validation.

## What Changes

- Add API v2 support for browser CORS preflight `OPTIONS` requests.
- Return a 2xx response, preferably `204 No Content`, for valid preflight requests without running endpoint business logic.
- Include `Access-Control-Allow-Origin: *`, `Access-Control-Allow-Methods`, `Access-Control-Allow-Headers: Authorization, Content-Type`, and `Access-Control-Max-Age: 86400` on preflight responses.
- Preserve `Access-Control-Allow-Origin: *` on real API v2 responses.
- Avoid duplicating CORS handling logic in every endpoint by centralizing shared behavior where practical.

## Capabilities

### New Capabilities
- `api-v2-cors-preflight`: API v2 endpoints support browser CORS preflight requests and include CORS headers on actual responses.

### Modified Capabilities

## Impact

- Affected code: PHP endpoints under `Server/htdocs/AppController/commands_RSM/api/v2/` and shared API utility helpers in `Server/htdocs/AppController/commands_RSM/utilities/`.
- Affected APIs: API v2 item, property, audit, file, picture, staff, and other v2 endpoints reached directly by browsers.
- Dependencies: no new external dependencies expected.
