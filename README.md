# Mini Assessment WordPress Plugin

Headless WordPress plugin for managing Assessments, Questions and Answers through the `assessment/v1` REST API.

## Features

- Custom database tables with activation/upgrade migrations.
- Public published-content API with pagination and search.
- Configurable role matrix for Assessment, Question and Answer actions.
- `assessment_manager` role and WordPress admin management screens.
- JWT access tokens (15 minutes) and rotating HttpOnly refresh-token sessions (7 days).
- Basic database-error logging that excludes credentials, tokens and request payloads.

## Installation

1. Copy this folder to `wp-content/plugins/wp-assessment-plugin`.
2. Activate **Mini Assessment Plugin** in WordPress Admin.
3. Open **Mini Assessment** in wp-admin to configure role permissions and manage data.

## Authentication

`POST /wp-json/assessment/v1/auth/login` returns a short-lived access token and sets an HttpOnly refresh cookie. Send the access token in `Authorization: Bearer <token>` for protected API calls. The SPA refreshes the access token automatically; the refresh cookie is rotated after each use.

For production, configure a unique WordPress `AUTH_KEY`, use HTTPS and set allowed front-end origins through the `wp_assessment_allowed_origins` filter.
