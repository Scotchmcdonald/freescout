# FreeScout Laravel 11 & PHP 8.2 Upgrade Plan

## Current State Analysis

### Version Information
- **Current Laravel**: 5.5.40 (Released: 2018)
- **Current PHP**: >= 7.1.0
- **Target Laravel**: 11.x (Latest LTS)
- **Target PHP**: ^8.2

### Critical Issues Identified

#### 1. Extensive Override System
The codebase uses a complex override system where vendor packages are replaced via PSR-4 autoloading:
- **30+ vendor packages** have overrides
- Includes **core Laravel framework** components
- Makes upgrading virtually impossible
- **Solution**: Remove all overrides and refactor custom logic using Laravel's extension patterns

#### 2. Composer.json Issues
- **Invalid key**: `fs-comment` in autoload section (line 94)
- **Deprecated license**: `AGPL-3.0` should be `AGPL-3.0-or-later`
- **Exact version constraints**: All 40+ dependencies are pinned to exact versions
- **Problematic scripts**: Post-autoload-dump manipulates vendor files with regex
- **Broken paths**: 266 files in `exclude-from-classmap` trying to prevent loading overridden files

#### 3. Abandoned/Outdated Packages
- `chumper/zipper` - Abandoned
- `fzaninotto/faker` - Replaced by `fakerphp/faker`
- `devfactory/minify` - Outdated, use Vite instead
- `lord/laroute` - Old routing helper
- `watson/rememberable` - Outdated caching

#### 4. Vendor Directory Committed
The repository commits the entire vendor directory, which is unusual but intentional for easier deployment.

## Upgrade Strategy

### Phase 1: Foundation Cleanup (PRIORITY: HIGH)
**Goal**: Fix composer.json and prepare for upgrade

1. **Fix composer.json validation errors**
   - Remove invalid `fs-comment` key
   - Update license to `AGPL-3.0-or-later`
   - Use version ranges instead of exact versions

2. **Remove override system**
   - Delete all PSR-4 override mappings from autoload
   - Delete all PSR-0 override mappings
   - Remove all paths from exclude-from-classmap
   - Delete post-autoload-dump regex manipulations

3. **Clean up overrides directory**
   - Analyze each override to understand why it was created
   - Document the changes made in each override
   - Identify which changes are still needed
   - Plan how to implement them using modern Laravel patterns

### Phase 2: Incremental Laravel Upgrade (PRIORITY: HIGH)
**Goal**: Upgrade Laravel from 5.5 to 11.x step-by-step

Laravel doesn't support jumping from 5.5 directly to 11. We must upgrade incrementally:

1. **5.5 → 5.6** - Minor changes
2. **5.6 → 5.7** - Email verification, better error messages
3. **5.7 → 5.8** - HasOneThrough relationship, authentication changes
4. **5.8 → 6.0** - Shift to semantic versioning
5. **6.0 → 7.0** - Symfony 5 components, new database changes
6. **7.0 → 8.0** - Laravel Jetstream, model factories
7. **8.0 → 9.0** - PHP 8.0 minimum, Flysystem 3.0
8. **9.0 → 10.0** - PHP 8.1 minimum, native types
9. **10.0 → 11.0** - PHP 8.2 minimum, slim application structure

**Alternative approach**: Start fresh with Laravel 11 and port the application code.

### Phase 3: PHP 8.2+ Compatibility (PRIORITY: HIGH)
**Goal**: Update code to use PHP 8.2+ features and fix incompatibilities

1. **Update syntax**
   - Replace array() with []
   - Use constructor property promotion
   - Add proper type hints
   - Add return types
   - Use readonly properties where appropriate
   - Use match expressions instead of switch
   - Use null-safe operator (?->)

2. **Fix deprecated features**
   - Update dynamic property usage
   - Fix utf8_encode/decode (removed in PHP 8.2)
   - Update create_function() usage (removed in PHP 8.0)
   - Fix each() usage (removed in PHP 8.0)

### Phase 4: Dependency Modernization (PRIORITY: MEDIUM)
**Goal**: Update all third-party packages

#### Replace Abandoned Packages
- `chumper/zipper` → `stechstudio/laravel-zipstream` or `laravel/filesystem`
- `fzaninotto/faker` → `fakerphp/faker`
- `devfactory/minify` → Remove, use Vite
- `lord/laroute` / `axn/laravel-laroute` → Remove, use Ziggy
- `watson/rememberable` → Built-in Laravel caching
- `rap2hpoutre/laravel-log-viewer` → `opcodesio/log-viewer`
- `rachidlaasri/laravel-installer` → Custom installer or Laravel Installer

#### Update Packages to Modern Versions
- `symfony/*`: 3.4.x → 7.x
- `doctrine/dbal`: 2.12 → 3.8+
- `guzzlehttp/guzzle`: 6.5 → 7.8+
- `nesbot/carbon`: 1.35 → 3.x (included with Laravel 11)
- `ramsey/uuid`: 3.9 → 4.7+
- `webklex/php-imap`: 4.1 → 5.3+
- `spatie/laravel-activitylog`: 2.7 → 4.8+
- `mews/purifier`: 3.2 → 3.4+
- `nwidart/laravel-modules`: 2.7 → 11.0+
- `barryvdh/laravel-translation-manager`: 0.5 → 0.6+

### Phase 5: Architecture Refactoring (PRIORITY: MEDIUM)
**Goal**: Modernize codebase architecture

1. **Authentication**
   - Update to Laravel 11 authentication system
   - Implement Sanctum for API authentication
   - Update middleware patterns

2. **Database Layer**
   - Update Eloquent models with modern patterns
   - Add proper type hints
   - Implement attribute casting
   - Update relationships to use modern syntax

3. **API Layer**
   - Implement Laravel API Resources
   - Add proper request validation
   - Implement rate limiting
   - Add versioning

4. **Configuration**
   - Update config files to Laravel 11 structure
   - Migrate to new application structure
   - Update environment variables

### Phase 6: Testing & Quality Assurance (PRIORITY: HIGH)
**Goal**: Ensure code quality and reliability

1. **Testing Framework**
   - Update PHPUnit to version 11
   - Update test structure to Laravel 11
   - Add feature tests for critical functionality
   - Add unit tests for business logic

2. **Static Analysis**
   - Add PHPStan/Larastan for static analysis
   - Configure at level 6 minimum
   - Fix all discovered issues

3. **Code Style**
   - Add Laravel Pint for code formatting
   - Configure PSR-12 standard
   - Format entire codebase

4. **Security**
   - Run CodeQL security scanning
   - Fix all critical and high severity issues
   - Update dependencies for security patches

### Phase 7: Asset Pipeline Modernization (PRIORITY: LOW)
**Goal**: Update frontend build system

1. Replace Webpack Mix with Vite
2. Update NPM dependencies
3. Update JavaScript to modern syntax
4. Optimize asset compilation

## Implementation Timeline

### Week 1-2: Foundation
- [x] Fix composer.json validation errors
- [ ] Document all overrides
- [ ] Remove override system structure
- [x] Update to PHP 8.2 requirement

### Week 3-4: Core Upgrade
- Incremental Laravel upgrade (5.5 → 11.0)
- Update core dependencies
- Fix breaking changes at each version

### Week 5-6: Refactoring
- Implement modern Laravel patterns for override functionality
- Update authentication system
- Modernize database layer
- Update API layer

### Week 7-8: Testing & Quality
- Add comprehensive test suite
- Implement static analysis
- Add code formatting
- Security audit

### Week 9-10: Finalization
- Asset pipeline modernization
- Documentation updates
- Performance optimization
- Final testing and deployment preparation

## Risk Assessment

### High Risk
- **Override removal**: May break core functionality if not carefully refactored
- **Incremental upgrades**: Each Laravel version has breaking changes
- **Data migrations**: Database changes could affect existing data

### Medium Risk
- **Package replacements**: Abandoned packages need suitable replacements
- **API compatibility**: External integrations may break
- **Performance**: New versions may have different performance characteristics

### Low Risk
- **Code style changes**: Purely cosmetic, low impact
- **Asset pipeline**: Doesn't affect backend functionality
- **Documentation**: No functional impact

## Rollback Plan

1. **Git branches**: Each phase in separate branch
2. **Database backups**: Before any migration
3. **Tagged releases**: Before major changes
4. **Testing**: Comprehensive testing at each phase
5. **Staging environment**: Test before production

## Success Criteria

- [ ] All composer.json validation errors resolved
- [ ] No vendor overrides remaining
- [ ] Laravel 11.x successfully installed
- [ ] PHP 8.2+ compatibility
- [ ] All dependencies updated to modern versions
- [ ] 80%+ test coverage
- [ ] PHPStan level 6+ passing
- [ ] Laravel Pint formatting applied
- [ ] No critical security issues
- [ ] All core functionality working
- [ ] Documentation updated

## Resources

- [Laravel 5.6 Upgrade Guide](https://laravel.com/docs/5.6/upgrade)
- [Laravel 6.0 Upgrade Guide](https://laravel.com/docs/6.x/upgrade)
- [Laravel 7.0 Upgrade Guide](https://laravel.com/docs/7.x/upgrade)
- [Laravel 8.0 Upgrade Guide](https://laravel.com/docs/8.x/upgrade)
- [Laravel 9.0 Upgrade Guide](https://laravel.com/docs/9.x/upgrade)
- [Laravel 10.0 Upgrade Guide](https://laravel.com/docs/10.x/upgrade)
- [Laravel 11.0 Upgrade Guide](https://laravel.com/docs/11.x/upgrade)
- [PHP 8.0 Migration Guide](https://www.php.net/manual/en/migration80.php)
- [PHP 8.1 Migration Guide](https://www.php.net/manual/en/migration81.php)
- [PHP 8.2 Migration Guide](https://www.php.net/manual/en/migration82.php)
