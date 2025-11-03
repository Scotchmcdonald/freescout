# Dependency Upgrade Strategy

## Current State
- Laravel 5.5.40
- PHP ^8.2 (just updated)
- 269 override files
- All packages pinned to exact versions

## Upgrade Strategy

We'll upgrade in phases to minimize risk:

### Phase 1: Remove Overrides, Keep Old Versions
First, we'll clean up composer.json to remove all override references while keeping package versions that are compatible:

1. Remove all PSR-4 override mappings
2. Remove PSR-0 override mappings  
3. Remove exclude-from-classmap entries
4. Remove post-autoload-dump scripts
5. Keep package versions mostly the same

This lets us test that the app works without overrides before upgrading packages.

### Phase 2: Upgrade Laravel to 6.x
Laravel 6 is the first LTS version after 5.5 and uses Semantic Versioning:
- Update laravel/framework to ^6.0
- Update related Symfony components to ^4.3
- Fix breaking changes

### Phase 3: Upgrade to Laravel 8.x
Laravel 8 adds major improvements:
- Update laravel/framework to ^8.0
- Update PHP requirement already met (8.2)
- Migrate to model factories
- Fix breaking changes

### Phase 4: Upgrade to Laravel 10.x
Laravel 10 is the latest LTS:
- Update laravel/framework to ^10.0
- Update Symfony to ^6.0
- Add native types
- Fix breaking changes

### Phase 5: Upgrade to Laravel 11.x
Latest version:
- Update laravel/framework to ^11.0
- Update Symfony to ^7.0
- Update all other packages
- Modern application structure

## Alternative: Start Fresh

Given the extent of overrides, it may be faster to:
1. Create new Laravel 11 app
2. Port FreeScout code
3. Implement custom logic using modern patterns

This is the recommended approach for this project.

## Package Version Targets

### Core Framework
- laravel/framework: 5.5.40 → ^11.0
- laravel/tinker: v1.0.7 → ^2.9

### Symfony Components (bundled with Laravel)
- symfony/*: 3.4.x → 7.x (via Laravel)

### Database & Validation
- doctrine/dbal: 2.12.1 → ^3.8
- egulias/email-validator: 2.1.10 → ^4.0

### Email & IMAP
- swiftmailer/swiftmailer: 6.1.* → symfony/mailer (Laravel 11)
- webklex/php-imap: 4.1.1 → ^5.3

### Utilities
- ramsey/uuid: 3.9.6 → ^4.7
- enshrined/svg-sanitize: 0.15.4 → ^0.20
- html2text/html2text: 4.1.0 → ^4.3
- mews/purifier: 3.2.2 → ^3.4

### Laravel Packages
- nwidart/laravel-modules: 2.7.0 → ^11.0
- spatie/laravel-activitylog: 2.7.0 → ^4.8
- tormjens/eventy: 0.5.4 → ^0.8
- barryvdh/laravel-translation-manager: v0.5.0 → ^0.6

### Packages to Replace
- chumper/zipper → Laravel Storage / ZipArchive
- fzaninotto/faker → fakerphp/faker
- devfactory/minify → Vite
- lord/laroute / axn/laravel-laroute → tightenco/ziggy
- watson/rememberable → Laravel caching
- rap2hpoutre/laravel-log-viewer → opcodesio/log-viewer
- rachidlaasri/laravel-installer → Custom or Laravel Installer
- codedge/laravel-selfupdater → Custom updater
- fideloper/proxy → fruitcake/laravel-cors or Laravel's built-in

### Development Tools
- barryvdh/laravel-debugbar: v3.2.0 → ^3.13
- filp/whoops: 2.14.5 → ^2.15
- mockery/mockery: 1.1.0 → ^1.6
- phpunit/phpunit: 9.5.28 → ^11.0

### New Additions
- laravel/pint (code style)
- larastan/larastan (static analysis)
- nunomaduro/collision (error handling)
- tightenco/ziggy (JS routing)

## Breaking Changes to Address

### Laravel 5.5 → 6.0
- String and array helpers moved to separate packages
- Model factory syntax changed
- Error page improvements

### Laravel 6.0 → 7.0
- Symfony 5 components
- CORS middleware changes
- Date serialization changes

### Laravel 7.0 → 8.0
- Model factory classes
- Pagination changes
- Rate limiting
- Time testing helpers
- Removed deprecated methods

### Laravel 8.0 → 9.0
- PHP 8.0+ required (already 8.2)
- Flysystem 3.0
- Anonymous migrations
- New query builder changes

### Laravel 9.0 → 10.0
- PHP 8.1+ required (already 8.2)
- Native type declarations
- Process improvements
- Dispatch::afterResponse()

### Laravel 10.0 → 11.0
- PHP 8.2+ required (✓ already done)
- Slim application structure
- Per-second rate limiting
- Health routing
- Reverb (WebSockets)

## Timeline Estimate

### Incremental Approach
- Phase 1 (Remove overrides): 2-3 weeks
- Phase 2 (Laravel 6): 1-2 weeks
- Phase 3 (Laravel 8): 1-2 weeks
- Phase 4 (Laravel 10): 1-2 weeks
- Phase 5 (Laravel 11): 2-3 weeks
**Total: 7-12 weeks**

### Fresh Start Approach
- Setup Laravel 11: 1 day
- Port application code: 2-3 weeks
- Implement custom logic: 2-3 weeks
- Testing: 2-3 weeks
**Total: 6-9 weeks**

## Recommendation

Given the extensive overrides (269 files) and the age of the codebase, I recommend the **Fresh Start Approach**:

1. Create new Laravel 11 application
2. Port FreeScout's business logic
3. Implement overrides' custom logic using modern patterns
4. Comprehensive testing
5. Deploy alongside old version for gradual migration

This approach is actually faster and results in cleaner, more maintainable code.
