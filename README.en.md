# PS-eNewsletter

[Deutsch](README.md) | **English**

[![Version](https://img.shields.io/badge/Version-1.1.2-2271b1?style=flat-square)](readme.txt)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4?style=flat-square&logo=php&logoColor=white)
![WordPress](https://img.shields.io/badge/WordPress-up%20to%207.1.0-21759b?style=flat-square&logo=wordpress&logoColor=white)
![ClassicPress](https://img.shields.io/badge/ClassicPress-2.7.3-03768e?style=flat-square)
[![License](https://img.shields.io/badge/License-GPL--2.0--or--later-2ea44f?style=flat-square)](https://www.gnu.org/licenses/gpl-2.0.html)

A self-hosted newsletter plugin for ClassicPress. PS-eNewsletter manages subscribers, content, delivery, and reporting directly in your own installation - without an external newsletter platform or recurring service fees.

## Features

- Create, preview, and version newsletters with the visual Builder V2
- Manage subscribers, import CSV files, and segment audiences with groups
- Send immediately, schedule delivery with WP-Cron, or apply delivery limits
- Use SMTP or the local mail transport; process POP3/IMAP bounces
- Track opens, bounces, and clicks; analyze campaigns with metrics and click drill-downs
- Run recurring campaigns and automations for new posts, products, and digests
- Provide subscription forms, double opt-in, and unsubscribe flows
- Support ClassicPress privacy export and erasure tools for personal data
- Optionally include audiences from PS Memberships and MarketPress products

## Installation

1. Copy the `e-newsletter` directory to `wp-content/plugins/`.
2. Activate the plugin in the ClassicPress administration area.
3. Open **PS-eNewsletter > Settings** and configure the sender address, delivery method, and delivery limit.
4. Create a group, then add or import subscribers.
5. Create a newsletter, edit it in the builder, and send a test email.

For multisite installations, the plugin can be network-activated. The menu is shown in Network Admin only when it is actually network-active.

## Typical Workflow

### Create and send a newsletter

1. Create a newsletter under **Newsletters**.
2. Use the **Newsletter Builder** to compose modules, presets, branding, and the responsive preview.
3. Send a test email to the configured preview address.
4. Select recipient groups or roles and send immediately, schedule a date, or use WP-Cron.

The builder stores its structured state as newsletter metadata and writes the rendered HTML to the existing newsletter record. The versions view lets you review and restore earlier states.

### Campaigns and automations

Under **Campaigns & Automations**, two types are available:

- **Campaign:** recurring delivery at hourly, daily, or weekly intervals.
- **Automation:** delivery when a post or product is published, plus scheduled weekly or monthly digests.

Each run records delivery, open, click, and bounce data. The statistics view provides key metrics, trends, top links, and the recipients behind individual clicks.

## Frontend Subscriptions

The plugin registers the following shortcodes:

| Shortcode | Purpose |
| --- | --- |
| `[enewsletter_subscribe]` | Subscription and subscription-management form. |
| `[enewsletter_unsubscribe_message]` | Message shown after an unsubscribe action. |
| `[enewsletter_subscribe_message]` | Message shown after a subscription action. |
| `[enews_product]`, `[enews_products]` | Product content for newsletters when MarketPress is available. |
| `[enews_post]`, `[enews_posts]`, `[enews_post_links]` | Post content and post links for newsletters. |

`[enewsletter_subscribe]` supports, among others, `show_name`, `show_groups`, and `subscribe_to_groups`.

## Delivery and Operations

### Delivery methods

Delivery can use the local PHP mail transport or SMTP. SMTP settings include a connection test plus host, port, security, and credential fields.

WP-Cron processes scheduled and queued deliveries. On low-traffic sites, configure a real server cron job to trigger the ClassicPress cron regularly so campaigns and delivery jobs run on time.

### Bounces

Bounce processing requires the PHP IMAP extension. Create a dedicated mailbox and enter its credentials under **Settings > Bounce Settings**.

### Debug Logging

Debug logging is disabled by default and can be enabled under **Settings**. Events can be filtered, downloaded, and cleared under **Logs**.

When debugging is enabled, the log is written to:

```
wp-content/uploads/e-newsletter/debug.log
```

You can also enable debugging explicitly with the `ENEWSLETTER_DEBUG` constant or the `email_newsletter_debug_enabled` filter.

## Privacy and Security

- Subscriber and delivery data remains in your own ClassicPress database.
- Double opt-in, unsubscribe links, and one-click unsubscribe are supported.
- Delivery and administrative actions use nonces and capability checks.
- Click tracking uses signed target links.
- The plugin registers exporters and erasers for the ClassicPress privacy tools.

The site operator remains responsible for the privacy configuration, including legal basis, retention periods, and privacy-policy notices.

## Development

The code uses the `email-newsletter` text domain. English Gettext files are in `languages/`:

- `email-newsletter.pot` - current template
- `email-newsletter-en_US.po` - English catalog
- `email-newsletter-en_US.mo` - compiled English catalog

Available extension points include:

```php
add_filter( 'email_newsletter_debug_enabled', function( $enabled ) {
    return $enabled;
} );

add_action( 'enewsletter_before_send', function( $newsletter_id ) {
    // Prepare a delivery.
} );

add_action( 'enewsletter_newsletter_saved', function( $newsletter_id, $data, $meta ) {
    // Respond to a saved newsletter.
}, 10, 3 );
```

Plugin metadata and the classic WordPress.org-compatible changelog are available in `readme.txt`.

## License

PS-eNewsletter is licensed under the [GNU General Public License v2.0 or later](https://www.gnu.org/licenses/gpl-2.0.html).