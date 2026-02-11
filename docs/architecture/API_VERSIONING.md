# API Versioning Strategy

**Last Updated:** February 8, 2026  
**Status:** Documented - Ready for Implementation  

---

## Overview

This document defines the API versioning strategy for the application, ensuring backwards compatibility while allowing the API to evolve over time.

## Versioning Approach

### Header-Based Versioning (Recommended)

**Why Header-Based?**
- Cleaner URLs
- Easier to version different aspects independently (API version vs data model version)
- Better for RESTful design
- Supports content negotiation

**Implementation:**
```php
// Client sends:
GET /api/clients
Accept: application/vnd.myapp.v2+json

// Server responds:
HTTP/1.1 200 OK
Content-Type: application/vnd.myapp.v2+json
X-API-Version: 2
```

**Route Configuration:**
```php
// routes/api.php
Route::middleware(['api', 'api.version'])->group(function () {
    Route::get('/clients', [ClientApiController::class, 'index']);
    Route::get('/clients/{id}', [ClientApiController::class, 'show']);
    Route::post('/clients', [ClientApiController::class, 'store']);
});
```

**Version Middleware:**
```php
// app/Http/Middleware/ApiVersionMiddleware.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiVersionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $version = $this->parseVersion($request);
        
        // Store version in request for controller access
        $request->attributes->set('api_version', $version);
        
        // Validate version
        if (!$this->isValidVersion($version)) {
            return response()->json([
                'error' => 'Invalid API version',
                'supported_versions' => ['1', '2'],
            ], 400);
        }
        
        $response = $next($request);
        
        // Add version header to response
        $response->headers->set('X-API-Version', $version);
        
        return $response;
    }
    
    private function parseVersion(Request $request): string
    {
        // Try Accept header first
        $accept = $request->header('Accept', '');
        if (preg_match('/vnd\.myapp\.v(\d+)\+json/', $accept, $matches)) {
            return $matches[1];
        }
        
        // Fallback to X-API-Version header
        $version = $request->header('X-API-Version');
        if ($version) {
            return $version;
        }
        
        // Default to latest stable version
        return '1';
    }
    
    private function isValidVersion(string $version): bool
    {
        return in_array($version, ['1', '2'], true);
    }
}
```

---

### Alternative: URL-Based Versioning

**When to Use:**
- Public API with many external integrators
- Need version to be immediately visible in URL
- Simpler for non-technical users to understand

**Implementation:**
```php
// routes/api.php
Route::prefix('api/v1')->group(function () {
    Route::get('/clients', [V1\ClientApiController::class, 'index']);
    Route::get('/invoices', [V1\InvoiceApiController::class, 'index']);
});

Route::prefix('api/v2')->group(function () {
    Route::get('/clients', [V2\ClientApiController::class, 'index']);
    Route::get('/invoices', [V2\InvoiceApiController::class, 'index']);
});
```

**Directory Structure:**
```
app/Http/Controllers/Api/
├── V1/
│   ├── ClientApiController.php
│   ├── InvoiceApiController.php
│   └── BaseController.php
├── V2/
│   ├── ClientApiController.php
│   ├── InvoiceApiController.php
│   └── BaseController.php
└── ApiController.php (shared logic)
```

---

## Versioning Rules

### What Requires a New Version?

**Breaking Changes (New Major Version Required):**
- Removing or renaming response fields
- Changing response data types (string → integer)
- Removing or renaming endpoints
- Changing authentication mechanisms
- Modifying error response formats
- Changing required request parameters

**Non-Breaking Changes (Same Version OK):**
- Adding new optional parameters
- Adding new response fields
- Adding new endpoints
- Expanding enum values (with validation bypass for old versions)
- Deprecating with warning (before removal)

**Example:**
```php
// V1: Original response
{
    "id": 123,
    "name": "Acme Corp",
    "created": "2024-01-01"
}

// V1: Non-breaking addition (OK)
{
    "id": 123,
    "name": "Acme Corp",
    "created": "2024-01-01",
    "updated": "2024-02-01"  // New field added
}

// V2: Breaking change (requires new version)
{
    "id": 123,
    "client_name": "Acme Corp",  // Renamed field
    "created_at": "2024-01-01T00:00:00Z"  // Changed format
}
```

---

## Version Compatibility Policy

### Support Windows

**Current Version (v2):**
- Full support for all features
- All bug fixes applied
- Performance optimizations
- Security patches

**Previous Version (v1):**
- Maintained for **12 months** after v2 release
- Security patches only
- Critical bug fixes only
- No new features

**Deprecated Versions:**
- **90-day sunset notice** before removal
- Prominent warnings in API responses
- Email notifications to API consumers
- Migration guide provided

### Version Support Timeline

```
┌─────────────┬─────────────┬─────────────┬─────────────┐
│   Release   │  Deprecate  │   Sunset    │   Remove    │
├─────────────┼─────────────┼─────────────┼─────────────┤
│    v2.0     │             │             │   Active    │
│  (Feb 2026) │             │             │             │
├─────────────┼─────────────┼─────────────┼─────────────┤
│    v1.0     │  Feb 2027   │  May 2027   │  Aug 2027   │
│  (Feb 2025) │ (12 months) │ (15 months) │ (18 months) │
└─────────────┴─────────────┴─────────────┴─────────────┘
```

---

## Response Format Standards

### Version Information in Responses

**Include version metadata in all responses:**
```json
{
    "meta": {
        "version": "2",
        "deprecated": false,
        "sunset_date": null
    },
    "data": {
        "id": 123,
        "name": "Acme Corp"
    }
}
```

**Deprecated version response:**
```json
{
    "meta": {
        "version": "1",
        "deprecated": true,
        "deprecation_date": "2027-02-01",
        "sunset_date": "2027-05-01",
        "migration_guide": "https://docs.example.com/api/v1-to-v2"
    },
    "data": {
        "id": 123,
        "name": "Acme Corp"
    }
}
```

### Error Responses

**Consistent across all versions:**
```json
{
    "error": {
        "code": "RESOURCE_NOT_FOUND",
        "message": "Client with ID 999 not found",
        "details": {
            "resource": "Client",
            "id": 999
        }
    },
    "meta": {
        "version": "2",
        "request_id": "req_1234567890"
    }
}
```

---

## Implementation Strategy

### Phase 1: Middleware & Routing (Week 1)

1. Create `ApiVersionMiddleware`
2. Register middleware in `app/Http/Kernel.php`
3. Add version parsing logic
4. Add version validation
5. Test with existing endpoints

### Phase 2: Controller Organization (Week 2)

1. Create `app/Http/Controllers/Api/V1` directory
2. Move current controllers to V1 namespace
3. Update route definitions
4. Add version-aware base controller
5. Test all existing endpoints still work

### Phase 3: Version 2 Implementation (Week 3-4)

1. Create `app/Http/Controllers/Api/V2` directory
2. Copy V1 controllers to V2
3. Implement breaking changes in V2
4. Create V2-specific resources/transformers
5. Update API documentation
6. Create migration guide

### Phase 4: Monitoring & Deprecation (Week 5)

1. Add API version tracking to observability
2. Monitor V1 vs V2 usage
3. Set up deprecation warnings
4. Communicate with API consumers
5. Create automated tests for both versions

---

## Version-Specific Logic

### Resource Transformers

Use Laravel API Resources for version-specific transformations:

```php
// app/Http/Resources/V1/ClientResource.php
namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created' => $this->created_at->format('Y-m-d'),  // V1 format
        ];
    }
}

// app/Http/Resources/V2/ClientResource.php
namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'client_name' => $this->name,  // Renamed in V2
            'created_at' => $this->created_at->toIso8601String(),  // V2 format
            'updated_at' => $this->updated_at->toIso8601String(),  // New in V2
        ];
    }
}
```

### Version-Aware Controllers

```php
// app/Http/Controllers/Api/V1/ClientApiController.php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\V1\ClientResource;

class ClientApiController extends ApiController
{
    public function index(Request $request)
    {
        $clients = Client::paginate(20);
        
        return ClientResource::collection($clients);
    }
}

// app/Http/Controllers/Api/V2/ClientApiController.php
namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\V2\ClientResource;

class ClientApiController extends ApiController
{
    public function index(Request $request)
    {
        // V2 supports additional filters
        $clients = Client::query()
            ->when($request->has('status'), fn($q) => $q->where('status', $request->status))
            ->paginate($request->get('per_page', 20));
        
        return ClientResource::collection($clients);
    }
}
```

---

## Testing Strategy

### Test Both Versions

```php
// tests/Feature/Api/V1/ClientApiTest.php
test('v1 returns clients in legacy format', function () {
    $client = Client::factory()->create(['name' => 'Acme Corp']);
    
    $response = $this->withHeaders([
        'Accept' => 'application/vnd.myapp.v1+json',
    ])->getJson('/api/clients');
    
    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'created']
            ],
            'meta' => ['version']
        ])
        ->assertJson([
            'meta' => ['version' => '1']
        ]);
});

// tests/Feature/Api/V2/ClientApiTest.php
test('v2 returns clients in new format', function () {
    $client = Client::factory()->create(['name' => 'Acme Corp']);
    
    $response = $this->withHeaders([
        'Accept' => 'application/vnd.myapp.v2+json',
    ])->getJson('/api/clients');
    
    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'client_name', 'created_at', 'updated_at']
            ],
            'meta' => ['version']
        ])
        ->assertJson([
            'meta' => ['version' => '2']
        ]);
});
```

---

## Documentation

### OpenAPI/Swagger Specification

Generate separate specs for each version:

```yaml
# api-docs/v1/openapi.yaml
openapi: 3.0.0
info:
  title: MyApp API
  version: "1.0"
  description: |
    **Status:** Deprecated (Sunset: May 2027)
    
    Please migrate to v2. See migration guide at:
    https://docs.example.com/api/v1-to-v2

paths:
  /api/clients:
    get:
      summary: List clients
      deprecated: true
      responses:
        '200':
          description: Success
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: array
                    items:
                      $ref: '#/components/schemas/ClientV1'
```

```yaml
# api-docs/v2/openapi.yaml
openapi: 3.0.0
info:
  title: MyApp API
  version: "2.0"
  description: Current stable version

paths:
  /api/clients:
    get:
      summary: List clients
      responses:
        '200':
          description: Success
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: array
                    items:
                      $ref: '#/components/schemas/ClientV2'
```

---

## Monitoring & Analytics

### Track Version Usage

```php
// app/Services/MetricsService.php
public function trackApiRequest(string $version, string $endpoint, int $statusCode): void
{
    Log::channel('business')->info('API request', [
        'version' => $version,
        'endpoint' => $endpoint,
        'status_code' => $statusCode,
    ]);
    
    // Track in Sentry
    if (function_exists('\\Sentry\\addBreadcrumb')) {
        \Sentry\addBreadcrumb(new \Sentry\Breadcrumb(
            \Sentry\Breadcrumb::LEVEL_INFO,
            \Sentry\Breadcrumb::TYPE_HTTP,
            'api.request',
            'API request',
            [
                'version' => $version,
                'endpoint' => $endpoint,
                'status_code' => $statusCode,
            ]
        ));
    }
}
```

### Weekly Version Report

Generate reports showing:
- % of requests per version
- Most-used endpoints per version
- Clients still on deprecated versions
- Migration progress

---

## Deployment Checklist

**Before releasing a new API version:**

- [ ] All breaking changes documented
- [ ] Migration guide created
- [ ] V1 tests still passing
- [ ] V2 tests passing
- [ ] API documentation updated
- [ ] Postman collection updated
- [ ] Email notification sent to API consumers
- [ ] Deprecation timeline announced
- [ ] Version middleware configured
- [ ] Monitoring dashboards updated
- [ ] Rollback plan documented

---

## Best Practices

### 1. **Default to Latest Stable**
If no version specified, use latest stable (currently v1, will be v2 once tested).

### 2. **Never Break Without Warning**
Always give 90-day minimum notice before removing deprecated versions.

### 3. **Keep It Simple**
Avoid micro-versions (1.1, 1.2). Stick to major versions only.

### 4. **Document Everything**
Every breaking change must be in migration guide with before/after examples.

### 5. **Monitor Usage**
Track which versions are being used to inform deprecation decisions.

### 6. **Test Thoroughly**
Every version should have full test coverage to prevent regressions.

---

## Future Considerations

### GraphQL Alternative

For more flexibility without versioning:
- Clients request only the fields they need
- Adding fields doesn't break existing clients
- Easier to evolve API over time

**When to consider:**
- Many API consumers with diverse needs
- Frequent schema changes
- Complex nested data requirements

---

**Next Steps:**
1. Implement `ApiVersionMiddleware`
2. Organize controllers into V1 namespace
3. Create API resource transformers
4. Set up version-aware routing
5. Add monitoring and tracking
6. Create OpenAPI documentation

**Owner:** Platform Team  
**Review:** Quarterly
