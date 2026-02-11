# Webhook Setup Guide

Complete guide for setting up and testing webhooks for external integrations (Google Workspace and Action1 RMM).

## Table of Contents

1. [Overview](#overview)
2. [Google Workspace Webhooks](#google-workspace-webhooks)
3. [Action1 RMM Webhooks](#action1-rmm-webhooks)
4. [Local Development with ngrok](#local-development-with-ngrok)
5. [Security Configuration](#security-configuration)
6. [Monitoring & Troubleshooting](#monitoring--troubleshooting)

## Overview

### What are Webhooks?

Webhooks enable real-time updates from external services instead of polling via scheduled jobs. When data changes in Google Workspace or Action1, they push notifications to our endpoints.

### Benefits

- **Real-time updates**: < 30 second delay from event to database
- **Reduced API calls**: Lower quota usage and costs
- **Better user experience**: Instant synchronization
- **Lower server load**: No continuous polling

### Architecture

```
┌─────────────────┐         ┌──────────────────┐         ┌─────────────────┐
│  Google/Action1 │ Push    │   Middleware     │ Pass    │   Controller    │
│                 │ ───────>│   - Verify sig   │ ───────>│   - Parse       │
│   External API  │         │   - Check IP     │         │   - Dispatch    │
└─────────────────┘         │   - Rate limit   │         │   - Log metrics │
                            └──────────────────┘         └─────────────────┘
                                                                  │
                                                                  v
                            ┌──────────────────┐         ┌─────────────────┐
                            │  EventDispatcher │         │   Listeners     │
                            │                  │ ───────>│  (Idempotent)   │
                            │  Module events   │         │  Update DB      │
                            └──────────────────┘         └─────────────────┘
```

## Google Workspace Webhooks

### Prerequisites

1. **Google Cloud Project** with Directory API enabled
2. **Service Account** with domain-wide delegation
3. **Admin credentials** stored in `storage/app/google-credentials.json`
4. **HTTPS endpoint** (required by Google - use ngrok for local dev)

### Step 1: Configure Google Credentials

Add to `.env`:

```env
GOOGLE_DOMAIN=yourdomain.com
GOOGLE_ADMIN_EMAIL=admin@yourdomain.com
GOOGLE_CREDENTIALS_PATH=/var/www/html/storage/app/google-credentials.json
```

### Step 2: Register Webhook Channel

Use Artisan tinker to register a webhook:

```php
php artisan tinker

$google = app(\Modules\GoogleAdmin\Services\GoogleWorkspaceService::class);

// Setup webhook for user directory changes
$channel = $google->setupWebhook(
    resourceType: 'users',
    webhookUrl: 'https://your-domain.com/api/webhooks/google/directory',
    ttlSeconds: 604800 // 7 days
);

// Result includes:
// - channel_id: Unique channel identifier
// - resource_id: Google's resource identifier
// - token: Verification token
// - expiration_time: When channel expires
```

### Step 3: Verify Webhook Reception

Check logs after registration:

```bash
tail -f storage/logs/laravel.log | grep "Google webhook"
```

Google immediately sends a "sync" message to verify the endpoint:

```
[INFO] Google Directory webhook received
[INFO] Google webhook sync message received
```

### Step 4: Test with Real Changes

Make a change in Google Admin Console (e.g., update a user) and verify:

```bash
tail -f storage/logs/laravel.log | grep "webhook"
```

Expected output:

```
[INFO] Google Directory webhook received
[INFO] Google webhook event dispatched
[INFO] Webhook processed: google/directory
```

### Supported Resource Types

| Resource Type | Endpoint | Events |
|--------------|----------|--------|
| `users` | `/api/webhooks/google/directory` | User created, updated, deleted |
| `groups` | `/api/webhooks/google/directory` | Group created, updated, deleted |
| `orgunits` | `/api/webhooks/google/directory` | OrgUnit created, updated, moved |
| `chrome_devices` | `/api/webhooks/google/chrome-devices` | Device status, policy changes |

### Channel Management

**List Active Channels:**

```bash
php artisan tinker

\App\Models\GooglePushChannel::active()->get();
```

**Stop a Channel:**

```php
$google = app(\Modules\GoogleAdmin\Services\GoogleWorkspaceService::class);
$channel = \App\Models\GooglePushChannel::find(1);

$google->stopWebhook($channel);
```

**Manual Renewal:**

```php
$google = app(\Modules\GoogleAdmin\Services\GoogleWorkspaceService::class);
$channel = \App\Models\GooglePushChannel::find(1);

$newChannel = $google->renewWebhook($channel, ttlSeconds: 604800);
```

### Automatic Renewal

Channels automatically renew via scheduled job:

```bash
# Check schedule
php artisan schedule:list

# Manually trigger renewal job
php artisan queue:work --once
```

The `RenewExpiringWebhooksJob` runs daily at 2:00 AM and renews channels expiring within 48 hours.

## Action1 RMM Webhooks

### Prerequisites

1. **Action1 Account** with API access
2. **Webhook Secret** from Action1 settings
3. **HTTPS endpoint** (use ngrok for local dev)

### Step 1: Configure Action1

Add to `.env`:

```env
ACTION1_API_KEY=your-api-key-here
ACTION1_WEBHOOK_SECRET=your-webhook-secret-here
```

### Step 2: Register Webhook in Action1 Console

1. Log into Action1 console
2. Navigate to **Settings → Webhooks**
3. Click **Add Webhook**
4. Configure:
   - **URL**: `https://your-domain.com/api/webhooks/action1/devices`
   - **Events**: Select `device.created`, `device.updated`, `device.status_changed`
   - **Secret**: Copy this to `.env` as `ACTION1_WEBHOOK_SECRET`

### Step 3: Test Webhook

Trigger a device event (e.g., restart a monitored device) and check logs:

```bash
tail -f storage/logs/laravel.log | grep "Action1"
```

### Supported Endpoints

| Endpoint | Events |
|----------|--------|
| `/api/webhooks/action1/devices` | Device created, updated, status changed |
| `/api/webhooks/action1/policies` | Policy compliance changes |
| `/api/webhooks/action1/alerts` | Security alerts, warnings |

## Local Development with ngrok

### Why ngrok?

Google/Action1 require HTTPS endpoints for webhooks. ngrok provides a secure tunnel to your local server.

### Setup

1. **Install ngrok:**

```bash
# macOS
brew install ngrok/ngrok/ngrok

# Linux
wget https://bin.equinox.io/c/bNyj1mQVY4c/ngrok-v3-stable-linux-amd64.tgz
tar -xvzf ngrok-v3-stable-linux-amd64.tgz
sudo mv ngrok /usr/local/bin/
```

2. **Authenticate ngrok:**

```bash
ngrok config add-authtoken YOUR_AUTH_TOKEN
```

3. **Start tunnel:**

```bash
ngrok http 80 --subdomain=your-app-name
```

Example output:

```
Forwarding  https://your-app-name.ngrok.io -> http://localhost:80
```

4. **Update webhook URLs** to use ngrok domain:

```
https://your-app-name.ngrok.io/api/webhooks/google/directory
https://your-app-name.ngrok.io/api/webhooks/action1/devices
```

### Tips for Local Testing

- **Keep ngrok running**: Closing ngrok breaks webhook delivery
- **Use static subdomain**: Free tier gives random URLs; paid plans allow custom subdomains
- **Check ngrok dashboard**: Visit http://127.0.0.1:4040 to see incoming requests
- **Inspect webhook payloads**: ngrok UI shows full request/response details

## Security Configuration

### Production Checklist

- [ ] **HTTPS enforced** (middleware rejects HTTP)
- [ ] **IP whitelist configured** (update middleware with actual IP ranges)
- [ ] **Webhook secrets** properly secured in `.env`
- [ ] **Rate limits** configured (60/minute per source)
- [ ] **Signature verification** enabled (no bypassing in production)
- [ ] **Security logging** to dedicated channel
- [ ] **Failed attempts** monitored and alerted

### IP Whitelist Configuration

Update `app/Http/Middleware/VerifyWebhookSignature.php`:

**Google IP Ranges:**

Check current ranges: https://www.gstatic.com/ipranges/goog.json

```php
private const GOOGLE_IP_RANGES = [
    '142.250.0.0/15',
    '172.217.0.0/16',
    // Add more CIDR ranges
];
```

**Action1 IP Ranges:**

Contact Action1 support for their webhook IP ranges.

### Signature Verification

**Google:**
- Uses `X-Goog-Channel-Token` header
- Token generated during channel setup
- Stored in `google_push_channels` table

**Action1:**
- Uses HMAC-SHA256 signature
- Format: `sha256=<hex_signature>`
- Payload: `timestamp.body`

## Monitoring & Troubleshooting

### Health Dashboard

View webhook channel status:

```
http://your-domain.com/webhooks/gateway
```

Shows:
- Active channels
- Expiration times
- Notification counts
- Health status

### Metrics

Webhook metrics are tracked in `app/Services/MetricsService.php`:

```php
trackWebhookReceived($source, $type)
trackWebhookProcessed($source, $type, $duration)
trackWebhookFailed($source, $type, $reason)
```

### Log Channels

- **`security`**: Failed authentications, replay attacks
- **`performance`**: Processing times, slow webhooks
- **`business`**: Webhook events dispatched

### Common Issues

#### 1. Webhook Not Receiving Notifications

**Symptoms:**
- No logs in `storage/logs/laravel.log`
- Channel shows no notifications

**Solutions:**
- Verify HTTPS endpoint is accessible
- Check firewall rules
- Test with `curl`:

```bash
curl -X POST https://your-domain.com/api/webhooks/google/directory \
  -H "X-Goog-Channel-Id: test" \
  -H "X-Goog-Channel-Token: test" \
  -H "X-Goog-Resource-Id: test" \
  -H "X-Goog-Resource-State: sync"
```

#### 2. Signature Verification Failed

**Symptoms:**
- HTTP 403 responses
- Logs show "signature verification failed"

**Solutions:**
- Verify token matches database (`google_push_channels`)
- Check webhook secret in `.env`
- Ensure ngrok isn't modifying headers

#### 3. Channel Expired

**Symptoms:**
- Channel marked expired in dashboard
- HTTP 403 on webhook delivery

**Solutions:**
- Manually renew channel
- Check renewal job is scheduled correctly
- Review job logs: `php artisan queue:work --once`

#### 4. Rate Limit Exceeded

**Symptoms:**
- HTTP 429 responses
- Some webhooks not processing

**Solutions:**
- Check rate limiter configuration
- Increase limits if legitimate traffic:

```php
// app/Providers/AppServiceProvider.php
RateLimiter::for('google_webhooks', function ($request) {
    return Limit::perMinute(120)->by($request->ip()); // Increased from 60
});
```

#### 5. Slow Processing

**Symptoms:**
- Processing time > 1 second
- Performance warnings in logs

**Solutions:**
- Optimize event listeners
- Move heavy processing to queued jobs
- Check database query performance

### Testing Webhooks

Run comprehensive test suite:

```bash
# All webhook tests
vendor/bin/pest tests/Feature/Webhooks/

# Security tests only
vendor/bin/pest tests/Feature/Webhooks/WebhookSecurityTest.php

# Google webhook tests
vendor/bin/pest tests/Feature/Webhooks/GoogleWebhookTest.php
```

### Manual Testing

**Test Google webhook:**

```bash
curl -X POST http://localhost/api/webhooks/google/directory \
  -H "X-Goog-Channel-Id: YOUR_CHANNEL_ID" \
  -H "X-Goog-Channel-Token: YOUR_TOKEN" \
  -H "X-Goog-Resource-Id: YOUR_RESOURCE_ID" \
  -H "X-Goog-Resource-State: sync" \
  -H "Content-Type: application/json"
```

**Test Action1 webhook:**

```bash
TIMESTAMP=$(date +%s)
SECRET="your-webhook-secret"
PAYLOAD='{"device_id":"test-123"}'
SIGNATURE="sha256=$(echo -n "${TIMESTAMP}.${PAYLOAD}" | openssl dgst -sha256 -hmac "${SECRET}" | cut -d' ' -f2)"

curl -X POST http://localhost/api/webhooks/action1/devices \
  -H "X-Action1-Signature: ${SIGNATURE}" \
  -H "X-Action1-Timestamp: ${TIMESTAMP}" \
  -H "X-Action1-Event: device.created" \
  -H "Content-Type: application/json" \
  -d "${PAYLOAD}"
```

## Additional Resources

- [Google Push Notifications Documentation](https://developers.google.com/admin-sdk/directory/v1/guides/push)
- [Action1 API Documentation](https://www.action1.com/documentation/)
- [ngrok Documentation](https://ngrok.com/docs)
- [Laravel Rate Limiting](https://laravel.com/docs/11.x/routing#rate-limiting)

## Support

For issues or questions:

1. Check logs: `tail -f storage/logs/laravel.log`
2. Review webhook dashboard: `/webhooks/gateway`
3. Run tests: `vendor/bin/pest tests/Feature/Webhooks/`
4. Check scheduled jobs: `php artisan schedule:list`
