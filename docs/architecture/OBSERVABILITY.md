# Observability Stack Documentation

**Last Updated:** February 8, 2026  
**Status:** Implemented and ready for deployment  

---

## Overview

This document describes the observability stack for the application, including error tracking, logging, performance monitoring, and metrics collection.

## Components

### 1. Sentry (Production Error Tracking)

**Purpose:** Centralized error tracking and performance monitoring in production.

**Installation:**
```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=YOUR_DSN_HERE
```

**Configuration:** See `config/sentry.php`

**Key Features:**
- Automatic error capture with stack traces
- Performance transaction tracking (10% sample rate)
- Breadcrumb trail for debugging context
- SQL query tracking (bindings scrubbed)
- User context tracking (no PII by default)
- Release tracking via Git commit hash

**Environment Variables:**
```bash
SENTRY_LARAVEL_DSN=https://your-dsn@sentry.io/project-id
SENTRY_ENVIRONMENT=production
SENTRY_TRACES_SAMPLE_RATE=0.1  # 10% of transactions
SENTRY_SEND_DEFAULT_PII=false  # GDPR compliant
```

**Ignored Exceptions:**
- `AuthenticationException` (normal login failures)
- `ValidationException` (form validation)
- `NotFoundHttpException` (404 errors)

---

### 2. Enhanced Logging Channels

**Purpose:** Structured logging for different application concerns.

**Configuration:** See `config/logging.php`

**Available Channels:**

#### `business` Channel
- **Purpose:** Business events and audit trail
- **Retention:** 90 days
- **Level:** info+
- **File:** `storage/logs/business.log`
- **Use Cases:**
  - Invoice generation
  - Payment processing
  - Contract changes
  - Entitlement updates

**Example:**
```php
Log::channel('business')->info('Invoice generated', [
    'client_id' => $clientId,
    'invoice_id' => $invoiceId,
    'amount_cents' => $amountCents,
]);
```

#### `performance` Channel
- **Purpose:** Performance metrics and slow operations
- **Retention:** 7 days
- **Level:** warning+
- **File:** `storage/logs/performance.log`
- **Use Cases:**
  - Slow HTTP requests (>1s)
  - Slow database queries (>1s)
  - Slow queue jobs (>30s)
  - API call latency

**Example:**
```php
Log::channel('performance')->warning('Slow API call', [
    'service' => 'action1',
    'endpoint' => '/api/computers',
    'duration_ms' => 3250,
]);
```

#### `security` Channel
- **Purpose:** Security-related events
- **Retention:** 90 days (compliance)
- **Level:** warning+
- **File:** `storage/logs/security.log`
- **Use Cases:**
  - Failed login attempts
  - Permission denied events
  - Suspicious activity
  - API key usage

**Example:**
```php
Log::channel('security')->warning('Failed login attempt', [
    'email' => $email,
    'ip' => $request->ip(),
    'user_agent' => $request->userAgent(),
]);
```

#### `queue` Channel
- **Purpose:** Queue job processing events
- **Retention:** 14 days
- **Level:** info+
- **File:** `storage/logs/queue.log`
- **Use Cases:**
  - Job execution times
  - Job failures and retries
  - Queue depth monitoring

**Example:**
```php
Log::channel('queue')->error('Job failed', [
    'job' => GenerateInvoiceJob::class,
    'attempts' => 3,
    'error' => $exception->getMessage(),
]);
```

---

### 3. Performance Tracking Middleware

**File:** `app/Http/Middleware/TrackPerformanceMetrics.php`

**Purpose:** Automatically track HTTP request performance.

**Features:**
- Tracks request duration and memory usage
- Logs slow requests (>1s warning, >3s error)
- Adds debug headers in non-production
- Integrates with Sentry transactions

**Thresholds:**
- **Slow request:** 1000ms (1 second) → warning log
- **Very slow request:** 3000ms (3 seconds) → error log

**Debug Headers (non-production only):**
```
X-Debug-Duration-Ms: 1234.56
X-Debug-Memory-Mb: 12.34
X-Debug-Queries: 42
```

**Registration:**
Add to `app/Http/Kernel.php`:
```php
protected $middleware = [
    // ... other middleware
    \App\Http\Middleware\TrackPerformanceMetrics::class,
];
```

---

### 4. Metrics Service

**File:** `app/Services/MetricsService.php`

**Purpose:** Centralized service for tracking custom business and performance metrics.

#### Methods

**`trackEvent(string $event, array $context, string $level)`**
Track generic business events.
```php
$metrics->trackEvent('invoice.generated', [
    'client_id' => 123,
    'invoice_id' => 456,
], 'info');
```

**`trackInvoiceGeneration(int $clientId, int $invoiceId, float $durationMs)`**
Track invoice generation performance.
```php
$startTime = microtime(true);
// ... generate invoice
$duration = (microtime(true) - $startTime) * 1000;
$metrics->trackInvoiceGeneration($clientId, $invoice->id, $duration);
```

**`trackPaymentProcessed(int $invoiceId, int $amountCents, string $gateway, bool $success)`**
Track payment processing events.
```php
$metrics->trackPaymentProcessed(
    $invoice->id,
    $payment->amount_cents,
    'helcim',
    $success
);
```

**`trackApiCall(string $service, string $endpoint, float $durationMs, int $statusCode)`**
Track external API call performance.
```php
$startTime = microtime(true);
$response = Http::get('https://api.action1.com/computers');
$duration = (microtime(true) - $startTime) * 1000;

$metrics->trackApiCall('action1', '/computers', $duration, $response->status());
```

**`trackSecurityEvent(string $event, array $context)`**
Track security-related events.
```php
$metrics->trackSecurityEvent('permission.denied', [
    'user_id' => $userId,
    'resource' => 'invoice',
    'action' => 'delete',
]);
```

**`trackQueueJob(string $jobClass, float $durationMs, bool $success, ?string $errorMessage)`**
Track queue job execution.
```php
$startTime = microtime(true);
try {
    // ... job logic
    $success = true;
    $error = null;
} catch (\Exception $e) {
    $success = false;
    $error = $e->getMessage();
} finally {
    $duration = (microtime(true) - $startTime) * 1000;
    $metrics->trackQueueJob(static::class, $duration, $success, $error);
}
```

---

## Usage Examples

### In Queue Jobs

```php
use App\Services\MetricsService;

class GenerateInvoiceJob implements ShouldQueue
{
    public function handle(MetricsService $metrics): void
    {
        $startTime = microtime(true);
        
        try {
            // Invoice generation logic
            $invoice = $this->generateInvoice();
            
            $duration = (microtime(true) - $startTime) * 1000;
            $metrics->trackInvoiceGeneration(
                $this->clientId,
                $invoice->id,
                $duration
            );
            
        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            $metrics->trackQueueJob(
                static::class,
                $duration,
                false,
                $e->getMessage()
            );
            throw $e;
        }
    }
}
```

### In Controllers

```php
use App\Services\MetricsService;

class InvoiceController extends Controller
{
    public function __construct(private MetricsService $metrics)
    {
    }
    
    public function store(Request $request)
    {
        // ... validation
        
        $invoice = Invoice::create($request->validated());
        
        $this->metrics->trackEvent('invoice.created', [
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'user_id' => auth()->id(),
        ]);
        
        return response()->json($invoice, 201);
    }
}
```

### With External API Calls

```php
use App\Services\MetricsService;
use Illuminate\Support\Facades\Http;

class Action1Service
{
    public function __construct(private MetricsService $metrics)
    {
    }
    
    public function getComputers(): array
    {
        $startTime = microtime(true);
        
        $response = Http::timeout(10)
            ->get('https://api.action1.com/v1/computers');
        
        $duration = (microtime(true) - $startTime) * 1000;
        
        $this->metrics->trackApiCall(
            'action1',
            '/v1/computers',
            $duration,
            $response->status()
        );
        
        return $response->json();
    }
}
```

---

## Testing the Stack

**Test all components:**
```bash
php artisan observability:test --all
```

**Test individual components:**
```bash
php artisan observability:test --logs
php artisan observability:test --sentry
php artisan observability:test --metrics
```

**Check log files:**
```bash
tail -f storage/logs/business.log
tail -f storage/logs/performance.log
tail -f storage/logs/security.log
tail -f storage/logs/queue.log
```

---

## Performance Impact

**Logging:**
- Minimal overhead (<1ms per log entry)
- Asynchronous writes to disk
- Log rotation prevents disk space issues

**Sentry:**
- 10% sample rate reduces overhead
- Automatic transaction tracking
- Breadcrumbs buffered in memory

**Metrics Service:**
- Microsecond-level timing precision
- No database writes (log-based)
- Can be disabled per environment

---

## Deployment Checklist

**Before deploying to production:**

- [ ] Install Sentry SDK: `composer require sentry/sentry-laravel`
- [ ] Configure `SENTRY_LARAVEL_DSN` in production `.env`
- [ ] Set `SENTRY_ENVIRONMENT=production`
- [ ] Set appropriate `SENTRY_TRACES_SAMPLE_RATE` (0.1 recommended)
- [ ] Test Sentry integration: `php artisan observability:test --sentry`
- [ ] Register `TrackPerformanceMetrics` middleware
- [ ] Configure log rotation (logrotate)
- [ ] Set up Sentry alerts (Slack/email)
- [ ] Create Sentry dashboard for key metrics
- [ ] Document baseline metrics (response times, error rates)

**Log file rotation:**
```bash
# Add to /etc/logrotate.d/laravel
/var/www/html/storage/logs/*.log {
    daily
    missingok
    rotate 90
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

---

## Monitoring Dashboards

**Recommended Sentry setup:**

1. **Error Tracking** Dashboard:
   - Error rate by module
   - Most common errors
   - Error trends over time

2. **Performance** Dashboard:
   - P50/P95/P99 response times
   - Slowest transactions
   - API call latency
   - Database query performance

3. **Business Metrics** Dashboard:
   - Invoice generation rate
   - Payment processing success rate
   - Queue processing times

**Alert Rules:**
- Error rate > 1% for 5 minutes → Slack/email alert
- P95 response time > 3s for 10 minutes → Slack alert
- Payment processing failure rate > 5% → Email alert
- Queue depth > 1000 jobs for 15 minutes → Slack alert

---

## Troubleshooting

**Sentry not capturing errors:**
```bash
# Check DSN is configured
php artisan tinker
>>> config('sentry.dsn')

# Test manually
php artisan observability:test --sentry
```

**Logs not appearing:**
```bash
# Check permissions
ls -la storage/logs/
chmod 755 storage/logs
touch storage/logs/test.log && rm storage/logs/test.log

# Check disk space
df -h
```

**Performance overhead concerns:**
```bash
# Disable in specific environments
# In .env:
SENTRY_TRACES_SAMPLE_RATE=0  # Disable performance tracking

# Remove middleware from specific routes
Route::middleware(['web'])->without([TrackPerformanceMetrics::class])
```

---

**Next Steps:**
1. Install Sentry: `composer require sentry/sentry-laravel`
2. Configure production DSN
3. Test with `php artisan observability:test --all`
4. Deploy to production
5. Monitor for 1 week and adjust thresholds
6. Create Sentry dashboards and alerts

**Owner:** Platform Team  
**Review:** Monthly  
