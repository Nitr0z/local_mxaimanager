# Moxis AI Manager

`local_mxaimanager`

## Description

Moxis AI Manager is a Moodle plugin for managing AI features. It provides a centralized hub for configuring AI providers (OpenAI, Mistral, Scaleway, Ollama, Nebius), registering AI features across plugins, and controlling usage through a prepaid credit wallet or token quota system.

## Architecture

```
┌──────────────────────────────────────────────────────────┐
│                   Admin Settings Page                     │
│  Billing & Quotas, Credit wallet, Managed providers     │
├──────────────────────────────────────────────────────────┤
│                   Manage UI Pages                         │
│  Providers (browse/add/edit/delete)                      │
│  Features  (browse/edit)                                 │
│  Quota/Credit bars (auto-switch based on mode)           │
├──────────────────────────────────────────────────────────┤
│                   Core Services                           │
│  action_handler  → enforce_quotas → credit_service       │
│  provider_resolver → default_provider configuration      │
│  quota_helper    → display data for templates            │
├──────────────────────────────────────────────────────────┤
│                   Data Layer                              │
│  providers, features, feature_actions, usage_logs        │
│  credit_ledger (wallet)                                  │
└──────────────────────────────────────────────────────────┘
```

## Admin Settings

All settings are accessible via **Site Administration → Plugins → Local plugins → Moxis AI Manager**.

### Billing & Quotas

| Setting | Description |
|---------|-------------|
| `quota_display_mode` | `credits` (simplified) or `tokens` (advanced) |
| `tokens_per_credit` | Conversion ratio (default: 10,000 tokens = 1 credit) |
| `managed_provider_ids` | Comma-separated provider IDs subject to credit limits (e.g. `1,3,5`) |

### Credit Wallet (Recharge Widget)

The settings page includes an embedded credit wallet widget:
- **Recharge form**: Amount + optional expiry date + note
- **Balance bar**: Visual progress with color coding (green → warning → danger)
- **Ledger history**: Full audit trail with toggle buttons to enable/disable individual entries

### Token Quotas (Advanced Mode)

When `quota_display_mode = tokens`, per-period token limits are available:
- Daily / Weekly / Monthly × Input / Output
- These fields are hidden when credit mode is active

## Provider Types

| Type | Credit-limited | Editable | Description |
|------|----------------|----------|-------------|
| **Managed** | ✅ Yes | ❌ Blocked | Added via UI, locked via `managed_provider_ids` setting |
| **Client-owned** | ❌ Unlimited | ✅ Full | Added via UI, not in managed list |

### Managed Provider IDs

To mark providers as credit-limited and non-editable:
1. Add providers via the UI normally (they get positive IDs like `1`, `2`, etc.)
2. Go to **Settings → Managed provider IDs**
3. Enter the IDs: `1, 2`
4. These providers are now:
   - Subject to credit/token limits
   - Non-editable and non-deletable by the client
   - Shown as "managed" in the providers table

## Credit Billing System

### How It Works

1. **Admin recharges** credits via the settings page (with optional expiry date per recharge)
2. **AI requests** consume credits based on: `(input_tokens + output_tokens) × multiplier / tokens_per_credit`
3. **Enforcement** happens in `action_handler::enforce_quotas()` before each AI call
4. **Balance** = SUM(active, non-expired ledger entries) - SUM(usage logs credits_used)

### Credit Multiplier

Each provider can have a `credit_multiplier` in its config JSON:
- `1.0` — Base rate (e.g., Scaleway)
- `3.0` — 3× more expensive (e.g., GPT-4)
- `0.0` — Free (e.g., self-hosted Ollama)

### Per-Recharge Expiry

Each credit allocation can have its own expiry date. When all active entries are expired, the system blocks usage with a dedicated error message.

### Soft-Disable (Toggle)

Individual ledger entries can be toggled active/inactive without deletion. This is useful for:
- Revoking a failed payment's credits
- Temporarily suspending an allocation
- Maintaining a full audit trail

## Database Schema

### `local_mxaimanager_credit_ledger`

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT (PK) | Auto-increment |
| `amount` | DECIMAL(10,2) | Credits added |
| `type` | CHAR(20) | `initial`, `recharge`, `adjustment` |
| `note` | TEXT | Free-text (e.g., invoice reference) |
| `created_by` | INT | Admin user ID |
| `timecreated` | INT | Unix timestamp |
| `expires_at` | INT (nullable) | Expiry timestamp for this batch |
| `active` | INT(1) | `1` = active, `0` = disabled |

### `local_mxaimanager_feature_action_usage_logs`

| Column | Type | Description |
|--------|------|-------------|
| `provider_id` | INT | Provider used for the request |
| `credits_used` | DECIMAL(10,2) | Credits consumed |
| *(+ existing columns)* | | feature_id, tokens, session, user, etc. |

## Supported Providers

| Provider | Chat | Embedding | Image | Transcription | TTS |
|----------|------|-----------|-------|---------------|-----|
| OpenAI | ✅ | ✅ | ✅ | ✅ | ✅ |
| Mistral | ✅ | ✅ | — | ✅ | — |
| Scaleway | ✅ | ✅ | ✅ | — | — |
| Ollama | ✅ | ✅ | — | — | — |
| Nebius | ✅ | ✅ | — | — | — |

## Localization

Fully translated in:
- 🇬🇧 English (`lang/en/`)
- 🇫🇷 French (`lang/fr/`)
- 🇩🇰 Danish (`lang/da/`)

All 135 language keys are synchronized across the three languages.

## Setup

Standard Moodle plugin installation:
1. Place in `local/mxaimanager/`
2. Run the Moodle upgrade process
3. Configure providers and credits via admin settings

## Capabilities

| Capability | Description |
|------------|-------------|
| `local/mxaimanager:manage_configuration` | Manage AI Manager settings and providers |

## Usage in Other Plugins

This plugin provides a way to register AI features and their actions, as well as a handler to use these features.

### Registering AI Features & Their Actions

To register AI features & their actions in your plugin (usually placed in `db/install.php` and/or `db/upgrade.php`):

```php
global $DB;

$transaction = $DB->start_delegated_transaction();
try {
    $base_factory = \local_mxaimanager\app\factory::make();

    /** Register an AI Feature **/
    $component = 'core';
    $feature_name_identifier = 'chat';
    $description_identifier = 'feature_chat_description';
    $feature = $base_factory->ai()->feature()->entity();
    $feature->set_component($component);
    $feature->set_name_identifier($feature_name_identifier);
    $feature->set_description_identifier($description_identifier);
    $feature->set_id($base_factory->ai()->feature()->repository()->insert($feature));

    /** Register an AI Feature's actions **/
    $feature_actions = [
        $base_factory->ai()->feature()->action()->entity()
            ->set_feature_id($feature->get_id())
            ->set_action_interface(\local_mxaimanager\app\ai\provider\providers\interfaces\chat_completion::class),
        $base_factory->ai()->feature()->action()->entity()
            ->set_feature_id($feature->get_id())
            ->set_action_interface(\local_mxaimanager\app\ai\provider\providers\interfaces\create_embedding::class),
    ];
    foreach ($feature_actions as $feature_action) {
        $base_factory->ai()->feature()->action()->repository()->insert($feature_action);
    }
    $transaction->allow_commit();
} catch (\Throwable $e) {
    $transaction->rollback($e);
}
```

### AI Usage

```php
$base_factory = \local_mxaimanager\app\factory::make();

/** Feature usage **/
$feature = $base_factory->ai()->feature()->repository()->get_by_component_and_name_identifier('core', 'chat');
$feature_handler = $base_factory->ai()->feature()->handler($feature);

/** Chat completion **/
$messages = [
    new \local_mxaimanager\app\ai\provider\message('system', 'You are only able to respond in JSON format.'),
    new \local_mxaimanager\app\ai\provider\message('user', 'Generate a list of three random colors.')
];
$response = $feature_handler->chat_completion($messages);

/** Create embedding **/
$embedding = $feature_handler->create_embedding(
    'Moodle is a learning platform.',
    1536
);
```

## GDPR

Usage logs store: feature_id, provider_id, request/response JSON, token counts, session_id, user_id, credits_used, and timestamp. These are covered by the Moodle privacy API metadata declarations.

## Changelog

* **1.6.0 (2026042303)**
    - **BREAKING**: Removed Freemium provider class and preconfigured provider system (negative IDs).
    - All providers are now database-backed. Use `managed_provider_ids` to lock providers.
    - Renamed settings page from "Freemium Provider" to "Billing & Quotas".
    - Simplified `provider_resolver` — no more auto-fallback to preconfigured defaults.
    - Simplified `action_handler` — quota enforcement based solely on `managed_provider_ids`.
    - Updated `quota_helper` — removed `is_using_preconfigured()` check.
    - Renamed `is_preconfigured` → `is_managed` in templates.
    - Removed 15 freemium-specific lang strings across EN/FR/DA.
* **1.5.0 (2026042301)**
    - **Prepaid Credit Wallet**: Ledger-based billing with per-recharge expiry and soft-disable toggle.
    - **Managed Provider IDs**: Admin setting to lock specific providers to credit limits (non-editable by clients).
    - **Credit Recharge Widget**: Embedded admin UI with balance bar, recharge form (amount + expiry + note), and auditable ledger history.
    - **Auto-switch quota display**: All manage pages use `get_display_data()` to show credits or tokens based on configured mode.
    - **Quota enforcement**: `check_balance()` now blocks requests when managed providers exist but no credits are allocated.
    - **Server-side protection**: Edit and delete actions blocked for managed providers in the controller.
    - **Danish translations**: Full DA localization (135 keys) including Scaleway, TTS, and credit system strings.
    - Removed obsolete global `credit_expiry_date` setting (replaced by per-recharge `expires_at`).
    - Updated `install.xml` with complete schema for fresh installations.
* **1.4.0 (2026042200)**
    - Credit system foundation: ledger table, `credit_service`, `quota_helper`, `tokens_per_credit` conversion.
    - Added `provider_id` and `credits_used` columns to usage logs.
    - Credit multiplier support per provider (`credit_multiplier` in config JSON).
* **1.3.0 (2026032700)**
    - Daily/weekly/monthly token quotas with period-based enforcement.
    - Quota display bars in manage pages.
* **1.0.5 (2026031600)**
    - Model fields now use autocomplete dropdowns with known models per provider.
    - Updated OpenAI/Mistral model lists.
    - Added French translation.
* **1.0.4 (2026011200)**
    - AI usage logging, image generation, audio transcription support.
* **1.0.3 (2026010800)**
    - JSON schema validation for AI provider responses.
* **1.0.2 (2025120400)**
    - Predefined AI providers, Nebius provider.
* **1.0.1 (2025111900)**
    - Vector API implementation.
* **1.0.0 (2025100100)**
    - Initial commit.

