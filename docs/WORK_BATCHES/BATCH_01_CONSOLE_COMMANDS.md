# Work Batch 01: Console Commands Implementation

**Batch ID**: BATCH_01  
**Category**: Console Commands  
**Priority**: 🔴 CRITICAL  
**Estimated Effort**: 22 hours  
**Parallelizable**: Yes (can work independently)  
**Dependencies**: User, Folder, Module models must exist

---

## Agent Prompt

You are implementing critical console commands for the FreeScout Laravel 11 application. These commands are essential for CLI administration and automation.

### Context

The modernized FreeScout app (Laravel 11) is missing 22 of 24 console commands from the archived app (Laravel 5.5). Your task is to implement the 8 highest priority commands that are blocking production deployment.

**Repository Location**: `/home/runner/work/freescout/freescout`  
**Target Directory**: `app/Console/Commands/`  
**Reference**: See `archive/app/Console/Commands/` for original implementations

### Reference Documentation

Before starting, review:
1. `docs/CRITICAL_FEATURES_IMPLEMENTATION.md` - Code examples for commands
2. `docs/ARCHIVE_COMPARISON_ROADMAP.md` - Section 1 (Console Commands)
3. `archive/app/Console/Commands/` - Original implementations

### Commands to Implement

#### 1. CreateUser Command (2h) - CRITICAL

**File**: `app/Console/Commands/CreateUser.php`

**Purpose**: Create user accounts via CLI for automation and initial setup

**Signature**: `freescout:create-user {--role=} {--firstName=} {--lastName=} {--email=} {--password=}`

**Requirements**:
- Accept role (admin/user), name, email, password as options or prompts
- Validate email format
- Check for existing users
- Hash passwords securely
- Set appropriate status and invite_state
- Support both interactive and non-interactive modes

**Reference**: See `docs/CRITICAL_FEATURES_IMPLEMENTATION.md` Section 1.1

**Testing**:
```bash
php artisan freescout:create-user --role=admin --firstName=Test --lastName=User --email=test@example.com --password=secret
```

---

#### 2. CheckRequirements Command (3h) - CRITICAL

**File**: `app/Console/Commands/CheckRequirements.php`

**Purpose**: Validate system requirements before installation/upgrade

**Signature**: `freescout:check-requirements`

**Requirements**:
- Check PHP version (>= 8.2.0)
- Verify PHP extensions: openssl, pdo, mbstring, tokenizer, xml, ctype, json, bcmath, imap, zip, gd, curl, intl
- Check required functions: proc_open, proc_close, fsockopen, symlink
- Verify directory permissions: storage, bootstrap/cache, public/storage
- Display colored output (green for OK, red for errors)

**Reference**: See `docs/CRITICAL_FEATURES_IMPLEMENTATION.md` Section 1.2

**Testing**:
```bash
php artisan freescout:check-requirements
```

---

#### 3. UpdateFolderCounters Command (2h) - MEDIUM

**File**: `app/Console/Commands/UpdateFolderCounters.php`

**Purpose**: Recalculate conversation counters for all folders

**Signature**: `freescout:update-folder-counters`

**Requirements**:
- Iterate through all folders
- Call `updateCounters()` method on each folder
- Show progress bar
- Display success message

**Reference**: See `docs/CRITICAL_FEATURES_IMPLEMENTATION.md` Section 1.3

**Note**: Requires `Folder::updateCounters()` method to exist in `app/Models/Folder.php`

**Testing**:
```bash
php artisan freescout:update-folder-counters
```

---

#### 4. ModuleInstall Command (4h) - CRITICAL

**File**: `app/Console/Commands/ModuleInstall.php`

**Purpose**: Install FreeScout modules (run migrations, create symlinks)

**Signature**: `freescout:module-install {module_alias?}`

**Requirements**:
- Accept optional module alias parameter
- If no alias, prompt to install all modules
- Clear cache before installation
- Run module migrations with `--force`
- Create public symlinks for module assets
- Handle errors gracefully
- Support bulk installation

**Reference**: See `docs/CRITICAL_FEATURES_IMPLEMENTATION.md` Section 1.4

**Dependencies**: Requires `nwidart/laravel-modules` package

**Testing**:
```bash
php artisan freescout:module-install my-module
php artisan freescout:module-install
```

---

#### 5. ModuleBuild Command (3h) - CRITICAL

**File**: `app/Console/Commands/ModuleBuild.php`

**Purpose**: Build module assets using Vite

**Signature**: `freescout:module-build {module_alias?}`

**Requirements**:
- Accept optional module alias
- If no alias, build all modules
- Run `npm run build` or Vite build for module
- Handle missing node_modules
- Show build output
- Report success/failure

**Testing**:
```bash
php artisan freescout:module-build my-module
```

---

#### 6. ModuleUpdate Command (3h) - CRITICAL

**File**: `app/Console/Commands/ModuleUpdate.php`

**Purpose**: Update installed modules

**Signature**: `freescout:module-update {module_alias?}`

**Requirements**:
- Accept optional module alias
- If no alias, update all modules
- Run migrations
- Rebuild assets
- Clear cache
- Handle version conflicts

**Testing**:
```bash
php artisan freescout:module-update my-module
```

---

#### 7. Update Command (4h) - CRITICAL

**File**: `app/Console/Commands/Update.php`

**Purpose**: Update FreeScout application

**Signature**: `freescout:update`

**Requirements**:
- Run database migrations
- Clear all caches
- Rebuild assets if needed
- Run post-update tasks
- Display update progress
- Handle errors gracefully

**Testing**:
```bash
php artisan freescout:update
```

---

#### 8. AfterAppUpdate Command (2h) - CRITICAL

**File**: `app/Console/Commands/AfterAppUpdate.php`

**Purpose**: Run post-update cleanup and optimization

**Signature**: `freescout:after-app-update`

**Requirements**:
- Clear all caches (config, route, view)
- Optimize autoloader
- Run any post-update scripts
- Display completion message

**Testing**:
```bash
php artisan freescout:after-app-update
```

---

## Implementation Guidelines

### Code Standards

1. **Use Laravel 11 conventions**:
   - Typed properties
   - Return type declarations
   - Modern PHP 8.2+ syntax

2. **Command structure**:
   ```php
   protected $signature = 'freescout:command-name {arg} {--option=}';
   protected $description = 'Clear description';
   
   public function handle(): int
   {
       // Implementation
       return 0; // Success
       return 1; // Failure
   }
   ```

3. **Output formatting**:
   - Use `$this->info()` for success messages
   - Use `$this->error()` for error messages
   - Use `$this->line()` for neutral output
   - Use `$this->comment()` for section headers
   - Use progress bars for long operations

4. **Error handling**:
   - Catch exceptions
   - Return appropriate exit codes
   - Display helpful error messages

### Required Model Methods

Ensure these methods exist in models:

**User Model** (`app/Models/User.php`):
```php
const ROLE_ADMIN = 1;
const ROLE_USER = 2;
const STATUS_ACTIVE = 1;
const INVITE_STATE_ACTIVATED = 1;
```

**Folder Model** (`app/Models/Folder.php`):
```php
public function updateCounters(): void
{
    $this->total_count = $this->conversations()->count();
    $this->active_count = $this->conversations()
        ->where('status', Conversation::STATUS_ACTIVE)
        ->count();
    $this->save();
}
```

### Testing Strategy

1. **Create feature tests** for each command:
   - Test with valid inputs
   - Test with invalid inputs
   - Test error conditions
   - Test output messages

2. **Manual testing**:
   - Run each command interactively
   - Test with different parameters
   - Verify database changes
   - Check file system changes

3. **Integration testing**:
   - Test command chains
   - Test with real modules
   - Verify side effects

### Deliverables

1. ✅ 8 command files in `app/Console/Commands/`
2. ✅ Feature tests in `tests/Feature/Console/`
3. ✅ Documentation in command descriptions
4. ✅ All commands registered in `app/Console/Kernel.php` (if not auto-discovered)
5. ✅ Updated `README.md` with command usage

### Success Criteria

- [ ] All 8 commands implemented and working
- [ ] Commands handle errors gracefully
- [ ] All tests passing
- [ ] Commands return appropriate exit codes
- [ ] Help text is clear and helpful
- [ ] Interactive and non-interactive modes work
- [ ] Progress indicators for long operations

### Time Estimate

- CreateUser: 2h
- CheckRequirements: 3h
- UpdateFolderCounters: 2h
- ModuleInstall: 4h
- ModuleBuild: 3h
- ModuleUpdate: 3h
- Update: 4h
- AfterAppUpdate: 2h
- Testing: 3h

**Total**: 22 hours

### Dependencies

- User model with constants
- Folder model with updateCounters()
- Module models and relationships
- nwidart/laravel-modules package

### Blockers

- None (models already exist)

### Notes

- Commands use Laravel's Artisan command system
- All commands should be namespaced with `freescout:` prefix
- Commands should work both interactively and in scripts
- Use dependency injection for models and services

---

## Validation Checklist

Before submitting:

- [ ] Ran `composer pint` (code style)
- [ ] Ran `composer phpstan` (static analysis)
- [ ] Ran `php artisan test` (all tests pass)
- [ ] Manually tested each command
- [ ] Verified exit codes
- [ ] Checked error handling
- [ ] Reviewed output formatting
- [ ] Updated documentation

---

**Batch Status**: Ready for implementation  
**Next Batch**: BATCH_02 (Models & Migrations)
