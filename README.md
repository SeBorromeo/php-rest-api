# PHP REST API

A lightweight, modular REST-style API built in pure PHP using a custom router, middleware pipeline, and clean separation of concerns. This project is designed as a learning and portfolio piece demonstrating core backend architecture concepts without relying on a heavy framework.

## Getting Started

This project is written entirely in PHP and uses Composer for dependency management.

### Installation

#### Install dependencies

```composer install```

#### Environment Variables

Create a `.env` file from `.envexample` in the project root.

#### Start PHP devolpment server

```php -S localhost:8000```

The API will be available at:

```http://localhost:8000/api```

## Overview

All requests are routed through a single front controller (index.php). Routes and middleware are registered there, and request handling is delegated to a custom routing system.

The API is designed to be:

- Stateless
- Modular
- Easy to extend
- Explicit in its architecture

## Routing

Routes are registered on a central Api object. There are two supported ways to define routes:
1. Direct route registration
Routes can be added by specifying an HTTP method, path, and handler.
2. Router modules
Related routes are grouped into routers and mounted under a base path.

This allows routes such as `/auth` and `/merch` to live in separate files for clarity and maintainability.

## Current Routes

### /auth

| Method | Path | Description |
|------|------|-------------|
| GET | `/auth/validateToken` | Validate an authentication token |
| GET | `/auth/extendToken` | Issue new authorization token given a valid token |
| POST | `/auth/login` | Get authorization token from credentials |
| POST | `/auth/logout` | Invalidate authorization token |

---

### /merch

| Method | Path | Description |
|------|------|-------------|
| GET | `/merch` | Retrieve all merchandise |
| GET | `/merch/{id}` | Retrieve a single merchandise item |
| POST | `/merch` | Create a new merchandise item |
| PUT | `/merch/reorder` | Update merchandise order |
| PUT | `/merch/{id}` | Update a single merchandise item |
| DELETE | `/merch/{id}` | Delete a merchandise item |


## Middleware

Middleware is used to handle cross-cutting concerns such as:

- Request body parsing
- Authentication and authorization
- Input validation

Middleware runs before route handlers and can short-circuit requests when necessary.