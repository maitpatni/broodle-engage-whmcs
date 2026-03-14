# Broodle Engage — WHMCS Provisioning Module

A WHMCS server provisioning module that automates the full lifecycle of [Chatwoot](https://www.chatwoot.com/) accounts on [Broodle Engage](https://engage.broodle.one) — from signup to termination.

> **Version:** 2.0.0 &nbsp;|&nbsp; **Requires:** WHMCS 8.x+, PHP 8.0+  
> **Author:** [Broodle](https://broodle.host) &nbsp;|&nbsp; **Platform:** [engage.broodle.one](https://engage.broodle.one)

---

## Features

- **Auto-provisioning** — creates a Chatwoot account + admin user the moment a WHMCS service is activated
- **Auto email inbox** — optionally creates an email inbox on provisioning
- **One-click SSO** — Auto Login button in the client portal (no password needed)
- **Full lifecycle** — suspend, unsuspend, terminate, and change package all work via WHMCS
- **Live stats** — client area shows real-time agent count, inbox count, conversations, and contacts pulled from the Chatwoot API
- **Branded emails** — welcome and password-reset email templates are auto-installed on first use
- **Admin SSO** — direct link to the Chatwoot super admin panel from the WHMCS server list

---

## Requirements

| Requirement | Version |
|---|---|
| WHMCS | 8.x or later |
| PHP | 8.0 or later |
| Chatwoot (Platform App) | Any self-hosted or Broodle Engage instance |

---

## Installation

### 1. Copy module files

```
/path/to/whmcs/modules/servers/broodleengage/
├── broodleengage.php
├── hooks.php
└── templates/
    └── clientarea.tpl
```

### 2. Create a Chatwoot Platform App

1. Log into your Chatwoot super admin panel: `https://engage.broodle.one/super_admin`
2. Go to **Platform Apps → New Platform App**, name it `WHMCS Integration`, and save
3. Copy the generated **Access Token** — this is your Platform API key

### 3. Add the server in WHMCS

1. Go to **Setup → Products/Services → Servers → Add New Server**
2. Fill in:
   - **Hostname:** `engage.broodle.one`
   - **Module:** `Broodle Engage`
   - **Access Hash:** *(paste your Platform App access token)*
   - **Secure:** ✅ SSL enabled
3. Click **Test Connection** to verify

### 4. Create a product

1. Go to **Setup → Products/Services → Create a New Product**
2. Set **Module** to `Broodle Engage`
3. Under **Module Settings**, configure:

| Option | Description | Default |
|---|---|---|
| Plan Name | Label shown to the customer (e.g. Starter, Pro) | `Starter` |
| Max Agents | Agent seat limit for this plan | `5` |
| Max Inboxes | Inbox limit for this plan | `3` |
| Auto Create Email Inbox | Create an email inbox on provisioning | `yes` |

---

## How It Works

### Provisioning flow

1. WHMCS calls `CreateAccount` on service activation
2. Module creates a Chatwoot account via the Platform API
3. Module creates a Chatwoot user and assigns them as account administrator
4. Optionally creates an email inbox using the user's access token
5. Credentials are stored in WHMCS and a welcome email is sent

### Single Sign-On (SSO)

The client portal shows an **Auto Login** button. Clicking it triggers WHMCS's built-in SSO mechanism, which calls `broodleengage_ServiceSingleSignOn()` and redirects the user to their Chatwoot dashboard pre-authenticated via `auth_token`.

### Email templates

Two templates are auto-installed on first provisioning:

- `Broodle Engage Welcome Email` — sent on account creation with login credentials
- `Broodle Engage Password Reset` — sent when an admin resets the service password

Customise them under **Setup → Email Templates** in WHMCS.

---

## API Endpoints Used

| Action | Method | Endpoint |
|---|---|---|
| Create account | `POST` | `/platform/api/v1/accounts` |
| Get / update account | `GET` / `PATCH` | `/platform/api/v1/accounts/{id}` |
| Suspend / unsuspend | `PATCH` | `/platform/api/v1/accounts/{id}` |
| Delete account | `DELETE` | `/platform/api/v1/accounts/{id}` |
| Create user | `POST` | `/platform/api/v1/users` |
| Update / delete user | `PATCH` / `DELETE` | `/platform/api/v1/users/{id}` |
| Assign user to account | `POST` | `/platform/api/v1/accounts/{id}/account_users` |
| Create inbox | `POST` | `/api/v1/accounts/{id}/inboxes` |

All account-level calls use the **Platform App token** (stored in WHMCS server Access Hash).  
Inbox creation uses the **user's own access token** returned at provisioning time.

---

## Database

The module creates and manages a single table: `mod_broodleengage`

| Column | Description |
|---|---|
| `service_id` | WHMCS service ID (unique) |
| `chatwoot_account_id` | Chatwoot account ID |
| `chatwoot_user_id` | Chatwoot user ID |
| `chatwoot_user_token` | User access token (used for SSO and API calls) |
| `chatwoot_email` | Email address used for the Chatwoot account |
| `chatwoot_status` | `active`, `suspended`, or `terminated` |
| `provisioned_at` | Timestamp of initial provisioning |

Schema migrations run automatically on each module call — safe to upgrade in place.

---

## File Structure

```
modules/servers/broodleengage/
├── broodleengage.php        # Main module (provisioning, SSO, client area)
├── hooks.php                # WHMCS hooks + email template installer
└── templates/
    └── clientarea.tpl       # Client portal UI (Smarty template)
```

---

## License

MIT — see [LICENSE](LICENSE) for details.
