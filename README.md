<p align="center">
  <h1 align="center">ProTask</h1>
  <p align="center">A collaborative project management app with an embedded AI assistant named <strong>Luna</strong>.</p>
</p>

<p align="center">
  <img alt="Laravel" src="https://img.shields.io/badge/Laravel-13.x-FF2D20?logo=laravel&logoColor=white">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white">
  <img alt="Tailwind CSS" src="https://img.shields.io/badge/Tailwind-4.x-06B6D4?logo=tailwindcss&logoColor=white">
  <img alt="Pest" src="https://img.shields.io/badge/Tests-61%20passing-22c55e">
  <img alt="License" src="https://img.shields.io/badge/License-MIT-blue">
</p>

---

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [AI Assistant (Luna)](#ai-assistant-luna)
- [Tech Stack](#tech-stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Environment Variables](#environment-variables)
- [Running the App](#running-the-app)
- [Default Account & Seed Data](#default-account--seed-data)
- [Roles & Permissions](#roles--permissions)
- [AI Configuration](#ai-configuration)
- [AI Troubleshooting](#ai-troubleshooting)
- [Project Structure](#project-structure)
- [Testing](#testing)
- [Security Notes](#security-notes)
- [Changelog](#changelog)
- [License](#license)

---

## Overview

ProTask is a Laravel-based project management platform for teams. It combines workspaces, projects, kanban boards, tasks, assignments, comments, labels, attachments, checklists, invitations, and an AI assistant that can perform real actions inside the app.

The assistant is branded **Luna** by default. Administrators can rename it, change its personality, and teach it app-specific rules without retraining or deploying new code.

---

## Features

### Workspaces & Projects
- Workspaces with multiple projects.
- Workspace members with roles: `owner`, `admin`, `member`.
- Project members with roles: `manager`, `member`.
- Project views: **Board**, **List**, **Calendar**, **Gantt**, and **Table**.
- Project timeline (`start_date`, `end_date`), project colors, starring, and descriptions.
- Workspace → project membership invariant: joining a project also joins its workspace.

### Tasks & Collaboration
- Kanban board with drag-and-drop ordering of lists and tasks.
- Subtasks through `parent_id`.
- Priorities: `low`, `medium`, `high`, `critical`.
- Due dates, completion state, and archive support.
- Assignees with “assigned by” tracking.
- Labels with colors.
- Comments.
- Checklists.
- File attachments (with automatic compression for large files).
- Task notifications and compact-card user preferences.

### Invitations & Onboarding
- Email invitations for workspaces and projects.
- Public invitation landing page.
- Accepting a project invitation joins both the project and the workspace.
- Accepting a workspace invitation requires choosing a project in that workspace.
- Onboarding flow for users without a workspace or project.

### Administration
- Global admin area (`/admin`):
  - Users: role, password, active status.
  - AI Agent: provider, model, key, assistant name, persona, knowledge notes.
  - Global settings.
  - Email templates.
  - Languages.
  - Backups (create, download, delete).
  - Legacy roles.
- Deactivated users are logged out automatically.
- Admin-only middleware on all admin routes.

### AI-Powered Workflow
- Natural-language task and project management.
- Executes actions through the same permission model as the UI.
- Conversation memory persisted server-side.
- Configurable persona and app knowledge.
- Automatic fallback model when the primary model is rate-limited or unavailable.

---

## AI Assistant (Luna)

Luna is not a separate service. She is a thin orchestration layer around OpenRouter chat completions:

```
User message
   ↓
AiAgentService::chat()
   ↓ builds compact app context + system prompt
OpenRouterService::chat()
   ↓ primary model → fallback model on 404/429/5xx/empty
LLM returns strict JSON: { "reply": "...", "actions": [...] }
   ↓
AiAgentService::executeAction() for each action
   ↓ permission checks + Eloquent writes
Action results returned to the UI
```

### Supported actions

| Action | Description |
|---|---|
| `create_workspace` | Create a workspace and make the user its owner. |
| `create_project` | Create a project inside a workspace. |
| `create_board_list` | Add a kanban column to a project. |
| `create_task` | Create a task with list, date, priority, parent, and assignees. |
| `update_task` | Update title, description, list, due date, priority, completion, or parent. |
| `assign_user` | Assign a project member to a task by user ID or email. |
| `add_comment` | Add a comment to a task. |
| `create_label` | Add a label with color to a task. |
| `update_project_timeline` | Change project start/end dates (manager permission required). |

Deletion is intentionally **not** available through Luna. Users must delete from the UI.

### Identity and knowledge

Luna's prompt is assembled from:

1. **Identity** — assistant name from `config/ai.php` or the admin UI.
2. **Personality** — tone instructions.
3. **Deployment facts** — primary and fallback model labels, derived from model IDs.
4. **Application context** — workspaces, projects, lists, members, and recent tasks (max 25 per project).
5. **App knowledge notes** — authoritative rules written by an administrator.
6. **Capability catalog** — the exact supported actions (`App\Support\AiCapabilities`).
7. **Few-shot examples** — JSON response examples for reliable formatting.
8. **Conversation history** — last 20 messages from the `ai_messages` table.

Model identity is grounded in configuration instead of the model guessing. For example, if the deployment uses Gemma with Nemotron fallback, Luna reports those facts.

### Conversation memory

- Successful turns are stored in `ai_messages` (`user` + `assistant` rows).
- Failed provider calls are **not** stored.
- Each user keeps the latest 50 messages; the prompt uses the latest 20.
- Memory is per user, survives refreshes, and is not trusted from the browser.

### Reliability behavior

| Provider result | Behavior |
|---|---|
| `401` / `403` | Clear “API key rejected” message. |
| `402` | Clear “out of credits” message. |
| `404` | Clear “model not found” message. |
| `429` | Retries on the fallback model, then reports rate limiting. |
| `5xx` | Retries on the fallback model, then reports temporary outage. |
| `200` with empty content | Retries on the fallback model, then reports an empty response. |
| Timeout | Graceful error message instead of a PHP fatal error. |
| Both models fail | Status-specific message in the chat UI. |

Additional safeguards:

- `set_time_limit(90)` before provider calls.
- HTTP timeout derived from PHP's execution limit (`min(75, max_execution_time - 5)`).
- `connectTimeout(10)` and `max_tokens: 3000`.
- Context is trimmed (25 tasks per project, descriptions truncated to 300 characters).
- Provider errors and empty responses are logged to `storage/logs/laravel.log`.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.3+, Laravel 13 |
| Frontend | Blade, Tailwind CSS 4, Vite 8, Bootstrap 5, Lucide icons |
| Database | PostgreSQL (SQLite in-memory for tests) |
| Testing | Pest 4 / PHPUnit |
| AI | OpenRouter chat completions API |
| Runtime | Laravel Octane / FrankenPHP (binary included), or `php artisan serve` |
| Auth scaffolding | Laravel UI |
| Code style | Laravel Pint |

---

## Requirements

- PHP **8.3+**
- Composer
- Node.js **18+** and npm
- PostgreSQL **13+** (recommended), or another Laravel-supported database
- OpenRouter API key (only required for Luna)

---

## Installation

```bash
# 1. Install PHP dependencies
composer install

# 2. Create the environment file
cp .env.example .env

# 3. Generate the application key
php artisan key:generate

# 4. Configure the database in .env
#    DB_CONNECTION=pgsql
#    DB_HOST=127.0.0.1
#    DB_PORT=5432
#    DB_DATABASE=protask
#    DB_USERNAME=your_user
#    DB_PASSWORD=your_password

# 5. Run migrations and seed demo data
php artisan migrate --seed

# 6. Install and build frontend assets
npm install
npm run build

# 7. Start the app
php artisan serve
```

Open the URL shown by the server (for example `http://127.0.0.1:8000`).

### Quick setup script

`composer setup` runs the common steps:

```bash
composer setup
```

It installs dependencies, creates `.env`, generates a key, migrates, installs npm packages, and builds assets.

---

## Environment Variables

| Variable | Default | Purpose |
|---|---|---|
| `APP_NAME` | `ProtaskV2` | Application name shown in the UI and AI metadata. |
| `APP_URL` | `http://localhost` | Base URL used by mail and OpenRouter referer headers. |
| `APP_ENV` | `local` | Environment. Use `production` in production. |
| `APP_DEBUG` | `true` | Disable in production. |
| `DB_CONNECTION` | `pgsql` | Database driver. |
| `DB_HOST` | `127.0.0.1` | Database host. |
| `DB_PORT` | `5432` | Database port. |
| `DB_DATABASE` | `mansur` | Database name. |
| `DB_USERNAME` | — | Database user. |
| `DB_PASSWORD` | — | Database password. |
| `SESSION_DRIVER` | `database` | Session storage. |
| `QUEUE_CONNECTION` | `database` | Queue connection. |
| `MAIL_MAILER` | `log` | Mail driver; use SMTP in production. |
| `OPENROUTER_API_KEY` | — | OpenRouter API key. Can be overridden in Admin → AI Agent. |
| `OPENROUTER_PRIMARY_MODEL` | `google/gemma-4-26b-a4b-it:free` | Primary chat model. |
| `OPENROUTER_FALLBACK_MODEL` | `nvidia/nemotron-3.5-super-vl:free` | Fallback chat model. |
| `AI_ASSISTANT_NAME` | `Luna` | Default assistant name. |
| `AI_ASSISTANT_PERSONA` | Warm, concise, practical | Default assistant tone. |
| `AI_ASSISTANT_KNOWLEDGE` | Built-in app rules | Default knowledge notes. |

> Settings saved through **Admin → AI Agent** take precedence over the corresponding `.env` values. The API key is encrypted in the database.

---

## Running the App

### Development

```bash
php artisan serve
npm run dev
```

Or run server, queue, logs, and Vite together:

```bash
composer dev
```

### Production build

```bash
npm run build
php artisan migrate --force
php artisan optimize
```

### FrankenPHP / Octane

The repository includes a `frankenphp` binary and `laravel/octane`. Use your preferred Octane command, for example:

```bash
php artisan octane:start --server=frankenphp
```

If you change `.env` or AI settings while Octane/FrankenPHP workers are running, restart the workers so the new configuration is loaded.

### Useful commands

```bash
php artisan optimize:clear     # Clear config, route, view, and event caches
php artisan migrate            # Run pending migrations
php artisan migrate:fresh --seed
php artisan pail               # Live log viewer
php artisan tinker
```

---

## Default Account & Seed Data

`php artisan migrate --seed` creates:

| Field | Value |
|---|---|
| Email | `admin@protask.local` |
| Password | `password` |
| Role | `admin` |

Seed data includes two workspaces, three projects, default board lists, and sample tasks.

> Change the seeded password immediately outside local development.

---

## Roles & Permissions

### Global roles

| Role | Access |
|---|---|
| `admin` | Full platform administration, including `/admin`. |
| `user` | Normal workspace/project usage. |

### Workspace roles

| Role | Capabilities |
|---|---|
| `owner` | Manage workspace, delete the workspace and its projects, manage members. |
| `admin` | Manage workspace members and invitations. |
| `member` | Access workspace projects they belong to. |

### Project roles

| Role | Capabilities |
|---|---|
| `manager` | Manage project settings, timeline, and members. |
| `member` | Work with tasks, comments, labels, and attachments. |

### AI permission rules

Luna can only act on workspaces, projects, and tasks the signed-in user can access. Every action is permission-checked in `AiAgentService`; invalid IDs or inaccessible resources return an error result instead of performing the action.

---

## AI Configuration

### Via the UI (recommended)

1. Sign in as an admin.
2. Open **Admin → AI Agent**.
3. Configure:
   - **Provider** — OpenRouter.
   - **Model** — exact OpenRouter model ID.
   - **Assistant name** — default `Luna`.
   - **Personality and tone** — short style instructions.
   - **App knowledge notes** — authoritative app rules (one per line).
   - **OpenRouter API key** — encrypted at rest.
   - **Enable AI assistant** — on/off.
4. Save.

### Via `.env`

```env
OPENROUTER_API_KEY=sk-or-v1-...
OPENROUTER_PRIMARY_MODEL=google/gemma-4-26b-a4b-it:free
OPENROUTER_FALLBACK_MODEL=nvidia/nemotron-3.5-super-vl:free
```

After changing `.env`:

```bash
php artisan optimize:clear
```

### Suggested models

| Model ID | Notes |
|---|---|
| `google/gemma-4-26b-a4b-it:free` | Default primary; free and fast, but frequently rate-limited upstream. |
| `google/gemma-4-31b-it:free` | Larger free Gemma variant. |
| `nvidia/nemotron-3.5-super-vl:free` | Default fallback; 1M context, good JSON compliance, different provider. |
| `nvidia/nemotron-3.5-lightning:free` | Faster, smaller; may spend too many tokens on reasoning. |
| `nvidia/nemotron-3-ultra-550b-a55b:free` | Strong reasoning, but slower and occasionally returns empty content. |
| `google/gemini-2.5-flash` | Paid, fast, reliable. |
| `google/gemma-4-31b-it` | Paid Gemma variant. |

> Important: a `:free` suffix only works if OpenRouter actually publishes a free variant for that exact model. For example, `nvidia/nemotron-3-super-120b-a12b` exists as a paid model, but `nvidia/nemotron-3-super-120b-a12b:free` does not.

---

## AI Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| `429 Provider returned error` | Free model is rate-limited upstream. | Luna retries the fallback automatically. Wait, use a paid model, or add your own provider key. |
| `404 model not found` | Invalid model ID, often an unavailable `:free` suffix. | Pick a valid ID from the Admin model suggestions. |
| `Maximum execution time ... exceeded` | Provider exceeded PHP's web execution limit. | Already mitigated with `set_time_limit(90)` and dynamic HTTP timeouts. Restart FrankenPHP/Octane after config changes. |
| `The model returned an empty response` | Model spent its token budget on reasoning or returned no content. | Luna retries the fallback. Increase `max_tokens` or use a more reliable model. |
| `OpenRouter rejected the API key` | Key is invalid, revoked, or cleared. | Save a new key in Admin → AI Agent. |
| `The OpenRouter account is out of credits` | No balance and a paid model is configured. | Add credits or switch to a free model. |
| AI says it is not configured | AI disabled, provider mismatch, or missing key/model. | Check Admin → AI Agent status banner. |
| Changes to `.env` not applied | Configuration cache or long-running workers. | Run `php artisan optimize:clear` and restart workers. |

Useful log:

```bash
tail -f storage/logs/laravel.log
```

---

## Project Structure

```
app/
├── Http/
│   ├── Controllers/          # Workspace, project, task, admin, AI controllers
│   └── Middleware/           # EnsureUserIsAdmin, EnsureUserIsActive
├── Mail/                     # InvitationMail
├── Models/                   # User, Workspace, Project, Task, AiMessage, ...
├── Services/
│   ├── AiAgentService.php    # Prompt building, action execution, memory
│   ├── AiSettings.php        # Admin-editable AI configuration
│   ├── AttachmentCompressor.php
│   ├── MembershipService.php # Workspace/project membership rules
│   └── OpenRouterService.php # HTTP client, fallback, error capture
└── Support/
    ├── AiCapabilities.php    # Single source of truth for supported actions
    └── ModelLabel.php        # Model ID → human-readable vendor/family

config/
├── ai.php                    # Assistant name, persona, default knowledge
└── services.php              # OpenRouter credentials and models

database/
├── migrations/               # Including ai_messages
└── seeders/DatabaseSeeder.php

resources/views/
├── ai/index.blade.php        # Luna chat UI
├── admin/                    # Admin pages, including admin/ai
├── projects/                 # Board, list, calendar, gantt, table, members
├── workspaces/
├── onboarding/
└── invitations/

routes/web.php                # Application and admin routes
tests/Feature/AiAgentTest.php # AI agent and memory tests
```

### Key AI files

| File | Responsibility |
|---|---|
| `app/Services/AiAgentService.php` | Builds context/prompt, calls the provider, executes actions, stores memory. |
| `app/Services/OpenRouterService.php` | Sends chat completions, retries fallback, reports status/error. |
| `app/Services/AiSettings.php` | Reads/writes provider, model, key, name, persona, knowledge. |
| `app/Support/AiCapabilities.php` | Action schema + user-facing capability summary. |
| `app/Support/ModelLabel.php` | Maps model IDs to labels like Gemma (Google) or Nemotron (NVIDIA). |
| `config/ai.php` | Default identity, persona, and app knowledge. |

---

## Testing

The project uses Pest with an in-memory SQLite database.

```bash
./vendor/bin/pest
# or
php artisan test
```

Run only the AI tests:

```bash
./vendor/bin/pest tests/Feature/AiAgentTest.php
```

Current status:

- **61 tests passing**
- **260 assertions**

AI test coverage includes:

- Settings encryption and admin-only access.
- Assistant persona and knowledge persistence.
- Prompt contents: identity, persona, knowledge, capabilities, model facts, examples.
- Action execution and permission boundaries.
- Fallback on `429`, empty responses, and invalid permissions.
- Timeout handling without crashes.
- Server-side conversation memory and non-persistence of failed turns.

---

## Security Notes

- OpenRouter API keys are encrypted before storage and masked in the admin UI.
- API keys are never sent to the browser.
- Admin routes require the `admin` global role.
- Deactivated users are logged out and blocked.
- AI actions are permission-checked against the authenticated user.
- Luna cannot delete data.
- Conversation history is stored server-side per user; browser-supplied history is no longer trusted.
- App knowledge notes are admin-only and never sourced from user chat.
- Use HTTPS and set `APP_DEBUG=false` in production.

---

## Changelog

All notable changes to ProTask are documented here.

### [1.2.0] — 2026-11-02

#### Added
- **Luna persona** — configurable assistant name (`Luna` by default) and personality.
- **Capability catalog** — `App\Support\AiCapabilities` is now the single source of truth for supported actions and user-facing capabilities.
- **Admin-editable app knowledge** — administrators can add authoritative rules in Admin → AI Agent; defaults live in `config/ai.php`.
- **Deployment facts in the prompt** — Luna can truthfully state which model family powers her instead of guessing.
- **Few-shot examples** — JSON response examples improve format reliability.
- **Server-side conversation memory** — new `ai_messages` table; the last 20 messages are replayed per user and pruned to 50.
- **Model label helper** — maps model IDs to vendor/family labels (`Gemma (Google)`, `Nemotron (NVIDIA)`, etc.).
- **Admin model suggestions** — updated datalist with currently valid OpenRouter model IDs.
- **Empty-response detection** — 200 responses with empty content are logged and retried on the fallback.

#### Changed
- Chat UI is branded **Luna**; sidebar, intro message, placeholder, and page title use the configured name.
- Context is smaller: 25 tasks per project (was 40) and project descriptions are truncated to 300 characters.
- AI requests now send `max_tokens: 3000`, `connectTimeout(10)`, and a dynamic HTTP timeout.
- `AiAgentService::chat()` raises PHP's execution limit with `set_time_limit(90)`.
- Default fallback changed to `nvidia/nemotron-3.5-super-vl:free` for speed and reliable JSON output.
- Browser no longer sends conversation `history`; the server owns conversation state.

#### Fixed
- **PHP fatal timeout** (`Maximum execution time of 30+2 seconds exceeded`) during slow OpenRouter calls.
- **Invalid fallback model** `nvidia/nemotron-3-super-120b-a12b:free` (404) replaced with a valid free model.
- **Misleading “check the API key” message** for rate limits, missing models, empty responses, and timeouts.
- **Empty assistant replies** from reasoning-heavy models now trigger fallback instead of a generic failure.
- Admin AI page could not display assistant identity or model labels.

#### Tests
- Added coverage for persona/knowledge saving, prompt contents, memory, failed-turn exclusion, empty-response retry, and timeout handling.
- Suite grew to **61 tests / 260 assertions**.

---

### [1.1.0] — 2026-10-31

#### Added
- **Automatic fallback model** — requests retry on `OPENROUTER_FALLBACK_MODEL` when the primary returns `404`, `429`, or `5xx`.
- **Provider error capture** — `OpenRouterService` exposes last status, error, and model used.
- **Status-specific error messages** — 401/403, 402, 404, 429, 5xx, and timeout cases are now distinguishable.
- **Admin fallback display** — the AI settings page shows the active fallback model and its family.
- **AI tests** for fallback behavior and rate-limit messaging.

#### Changed
- OpenRouter requests now log the failing model, chosen fallback, and status.

#### Fixed
- Generic “could not reach the AI provider” message replaced with actionable guidance.
- OpenRouter HTTP failures no longer collapse into an indistinguishable `null`.

---

### [1.0.0] — 2026-09-09

#### Added
- Initial ProTask release.
- Workspaces, projects, kanban boards, lists, tasks, subtasks, priorities, and due dates.
- Project views: board, list, calendar, gantt, table, and members.
- Assignees, labels, comments, checklists, and attachments.
- Invitations and onboarding flow.
- Workspace/project role model and membership service.
- Admin area: users, settings, email templates, languages, backups, roles.
- AI assistant integration with OpenRouter.
- Authentication scaffolding and user preferences.
- Pest test suite and seed data.

---

## License

ProTask is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
