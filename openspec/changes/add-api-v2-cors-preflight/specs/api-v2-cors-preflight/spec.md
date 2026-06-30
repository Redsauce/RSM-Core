## ADDED Requirements

### Requirement: API v2 endpoints handle CORS preflight
The system SHALL allow browser CORS preflight requests to API v2 endpoint files without requiring endpoint-specific authentication, request body validation, or business logic execution.

#### Scenario: Preflight request succeeds
- **WHEN** a browser sends an `OPTIONS` request to an API v2 endpoint with `Origin`, `Access-Control-Request-Method`, and `Access-Control-Request-Headers`
- **THEN** the system SHALL return a 2xx response without requiring an API token or JSON body

#### Scenario: Preflight request has no response body
- **WHEN** the system responds to an API v2 preflight request
- **THEN** the response MUST NOT include endpoint data or execute the endpoint operation

### Requirement: API v2 preflight responses include CORS headers
The system SHALL include the required CORS allow headers on API v2 preflight responses.

#### Scenario: GET endpoint preflight headers
- **WHEN** a browser sends `OPTIONS /AppController/commands_RSM/api/v2/items/get.php` with `Access-Control-Request-Method: GET` and `Access-Control-Request-Headers: authorization,content-type`
- **THEN** the system SHALL return `Access-Control-Allow-Origin: *`
- **THEN** the system SHALL return `Access-Control-Allow-Methods` containing `GET`, `POST`, and `OPTIONS`
- **THEN** the system SHALL return `Access-Control-Allow-Headers: Authorization, Content-Type`
- **THEN** the system SHALL return `Access-Control-Max-Age: 86400`

#### Scenario: Endpoint method is reflected in allowed methods
- **WHEN** a browser sends a preflight request to an API v2 endpoint whose real method is not `GET`
- **THEN** the system SHALL return `Access-Control-Allow-Methods` containing that endpoint method and `OPTIONS`

### Requirement: API v2 real responses remain CORS accessible
The system SHALL keep API v2 real endpoint responses accessible to browser clients after a successful preflight.

#### Scenario: Items get supports GET and POST
- **WHEN** a client sends a real request to `/AppController/commands_RSM/api/v2/items/get.php` using `GET` or `POST`
- **THEN** the endpoint SHALL accept the request method and continue applying its existing authentication, body validation, permission, and response behavior

#### Scenario: Real request includes allow origin
- **WHEN** a browser sends the real API v2 request after preflight succeeds
- **THEN** the system SHALL include `Access-Control-Allow-Origin: *` on the real response

#### Scenario: Existing endpoint behavior is preserved
- **WHEN** a non-OPTIONS request reaches an API v2 endpoint
- **THEN** the endpoint SHALL continue to apply its existing request method, authentication, body validation, permission, and response behavior
