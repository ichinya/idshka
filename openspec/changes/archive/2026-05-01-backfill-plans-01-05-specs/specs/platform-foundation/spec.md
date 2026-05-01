## ADDED Requirements

### Requirement: Laravel Platform Runtime
The platform SHALL run as a Laravel-first application with a defined web, API, OAuth, and operational route surface.

#### Scenario: Application exposes configured route groups
- **WHEN** the application boots
- **THEN** it SHALL load web routes for browser/session flows, API routes for versioned JSON endpoints, OAuth routes for issuer endpoints, and operational probe routes for service health.

#### Scenario: Domain boundaries are present
- **WHEN** new product behavior is added
- **THEN** domain logic SHALL live under `app/Domain/*` or explicit contract namespaces rather than being embedded directly in framework boot files.

### Requirement: Operational Probes
The platform SHALL provide JSON health and readiness probes suitable for local runtime and CI smoke checks.

#### Scenario: Health probe is requested
- **WHEN** a client requests the public health endpoint
- **THEN** the service SHALL return deterministic JSON identifying the service status.

#### Scenario: Readiness probe is requested
- **WHEN** an allowed internal readiness probe is requested
- **THEN** the service SHALL check required runtime dependencies and return JSON without exposing sensitive connection details.

#### Scenario: Public client requests internal readiness
- **WHEN** a non-internal client requests readiness
- **THEN** the service SHALL fail closed with a forbidden response.

### Requirement: Request Correlation
Every HTTP response SHALL carry a bounded request id for tracing and logs.

#### Scenario: Request has no trusted id
- **WHEN** a request enters the application without a valid request id
- **THEN** the application SHALL generate one and attach it to response headers and log context.

#### Scenario: Request supplies an invalid id
- **WHEN** a client supplies a malformed or oversized request id
- **THEN** the application SHALL sanitize or replace it before using it.

### Requirement: Local Container Runtime
The repository SHALL include a local container runtime for the Laravel app and its required infrastructure.

#### Scenario: Local runtime is configured
- **WHEN** Docker Compose configuration is evaluated
- **THEN** it SHALL define app, web server, database, cache, and gateway-related services with explicit networks and volumes.

#### Scenario: CI validates runtime configuration
- **WHEN** CI runs
- **THEN** it SHALL validate dependency metadata, install dependencies, build frontend assets, run tests, and validate Compose configuration.
