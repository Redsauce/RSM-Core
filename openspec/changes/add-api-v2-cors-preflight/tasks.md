## 1. Shared CORS Handling

- [x] 1.1 Add shared API CORS helper functions in the utilities layer for origin, allowed methods, allowed headers, max age, and preflight termination.
- [x] 1.2 Ensure the preflight helper returns a 2xx response with no endpoint data and exits before authentication, body parsing, or business logic.
- [x] 1.3 Ensure real API responses can continue to send `Access-Control-Allow-Origin: *` through the shared helper or existing equivalent behavior.

## 2. API v2 Endpoint Coverage

- [x] 2.1 Audit all direct PHP endpoint files under `Server/htdocs/AppController/commands_RSM/api/v2/`.
- [x] 2.2 Add preflight handling to each API v2 endpoint before `checkCorrectRequestMethod()` and request-body validation.
- [x] 2.3 Configure each endpoint's allowed method so `Access-Control-Allow-Methods` contains the real method and `OPTIONS`.
- [x] 2.4 Remove or avoid duplicate endpoint-local CORS header boilerplate where the shared helper replaces it cleanly.

## 3. Verification

- [x] 3.1 Run PHP syntax checks on all modified PHP files.
- [ ] 3.2 Verify `OPTIONS /api/v2/items/get.php` returns 2xx and includes `Access-Control-Allow-Origin: *`, `Access-Control-Allow-Methods` containing `GET`, `POST`, and `OPTIONS`, `Access-Control-Allow-Headers: Authorization, Content-Type`, and `Access-Control-Max-Age: 86400`.
- [ ] 3.3 Verify representative non-GET endpoints, including `items/create.php`, return 2xx preflight responses with the correct method in `Access-Control-Allow-Methods`.
- [ ] 3.4 Verify real API v2 requests still include `Access-Control-Allow-Origin: *` and preserve existing authentication, validation, permission, and response behavior.
