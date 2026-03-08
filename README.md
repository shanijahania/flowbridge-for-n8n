# FlowBridge (n8n Edition)

WordPress plugin that sends real-time webhook events to n8n for posts, taxonomies, users, and Contact Form 7 submissions. Configure fields, map names, preview JSON payloads, and test webhooks — all from the admin UI.

## Features

- **Post & Custom Post Type Events** — Send webhooks when posts are created, updated, deleted, or change status.
- **Taxonomy Events** — Trigger webhooks on term creation, update, or deletion for any taxonomy.
- **User Events** — Notify n8n when users register, update their profile, or are deleted.
- **Contact Form 7 Integration** — Forward CF7 form submissions to n8n with field-level control.
- **Field Configuration** — Choose exactly which fields to include, rename them with "Send As", and set data types (string, int, float, bool, JSON).
- **Meta Field Detection** — Automatically discovers post meta, term meta, and user meta from sample entities.
- **JSON Preview** — See the exact payload structure before saving or sending, directly in the configuration modal.
- **Test Events** — Send test payloads to a separate test webhook URL so you can build n8n workflows without triggering production.
- **Webhook Logs** — Paginated log viewer with event type, HTTP status, payload inspection, and response details.
- **Per-Entity Toggles** — Enable or disable webhook sending for individual post types, taxonomies, or forms without losing configuration.

## Requirements

- WordPress 6.0+
- PHP 7.4+
- An [n8n](https://n8n.io) instance with a Webhook node

## Installation

1. Upload the `flowbridge-for-n8n` folder to `/wp-content/plugins/`.
2. Activate the plugin via **Plugins > Installed Plugins**.
3. Navigate to **FlowBridge > Settings** and enter your n8n webhook URL.

## Quick Start

1. **Set your webhook URL** — Paste your n8n Webhook node URL in the Webhook tab. Optionally add a separate test URL.
2. **Configure an entity** — Go to the Posts, Taxonomies, Users, or Contact Forms tab. Click **Configure** on any entity.
3. **Select events** — Check which events should trigger a webhook (e.g., Created, Updated, Deleted).
4. **Pick fields** — Load fields from a sample entity, enable/disable individual fields, rename them, and set types.
5. **Preview** — Click **Preview Output** to see the exact JSON that will be sent.
6. **Save & enable** — Save the configuration and flip the toggle to start sending events.

## Webhook Payload Structure

```json
{
  "event": "post.created",
  "site_url": "https://yoursite.com",
  "timestamp": "2026-03-08T12:00:00+00:00",
  "entity_type": "post",
  "entity_subtype": "post",
  "entity_id": 42,
  "data": {
    "title": "Hello World",
    "slug": "hello-world",
    "status": "publish",
    "meta": {
      "custom_field": "value"
    }
  }
}
```

The `data` object only contains the fields you enabled, using the "Send As" names you configured.

## License

GPL-2.0-or-later — see [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).
