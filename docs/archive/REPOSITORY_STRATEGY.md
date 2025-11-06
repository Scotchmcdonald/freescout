# Modernization Repository & Branch Strategy

## Question
**"Should this start in a new repo/branch?"**

## UPDATED: Recommended Approach - In-Place Archive

**Context Update**: This repository is used for **reference only**, not production deployment. Production uses the official freescout repo.

Therefore, we can safely modernize **in-place** by archiving the old code.

### ✅ CHOSEN APPROACH: Archive Legacy, Develop Modern (Same Repo)

**See IN_PLACE_MODERNIZATION.md for complete implementation details.**

#### Structure:
```
Scotchmcdonald/freescout/
├── archive/              # OLD CODE (read-only reference)
│   ├── app/             # Laravel 5.5 application  
│   ├── overrides/       # 269 override files
│   └── ...              # All legacy code
│
├── app/                 # NEW: Laravel 11 application
├── config/              # NEW: Modern configuration
└── ...                  # All modern code
```

#### Why This Works:
1. ✅ **Single repository**: Simpler workflow
2. ✅ **Reference available**: Old code in `archive/` folder
3. ✅ **No production risk**: This repo is reference-only
4. ✅ **Clean separation**: `archive/` vs root directories
5. ✅ **Git history preserved**: Shows transformation journey

#### Implementation:
1. Move all legacy code to `archive/`
2. Initialize Laravel 11 in root
3. Port functionality incrementally, referencing `archive/`
4. Archive stays as read-only reference

---

## Previous Consideration: New Repository

*The analysis below was the initial recommendation before understanding this repo's reference-only usage.*

### Why a New Repository is Best

#### 1. Clean Separation of Concerns
- **Legacy codebase** (Laravel 5.5, PHP 7.1, 269 overrides) remains intact and operational
- **Modern codebase** (Laravel 11, PHP 8.2, clean architecture) developed independently
- No risk of breaking the current production application during development
- Clear boundary between old and new systems

#### 2. Different Technology Stacks
```
Legacy Stack:
- Laravel 5.5.40
- PHP 7.1+
- 269 vendor overrides
- Webpack Mix
- Old dependencies

Modern Stack:
- Laravel 11.x
- PHP 8.2+
- Clean vendor directory
- Vite
- Modern dependencies
```

These are fundamentally incompatible - they cannot coexist in the same repository without conflicts.

#### 3. Git History Management
- **Legacy repo**: Preserves historical context, commits, and documentation
- **Modern repo**: Starts with clean history focused on modernization
- Easier code reviews (reviewers aren't confused by old override code)
- Better git blame/log clarity

#### 4. Deployment Flexibility
- Both versions can run in parallel during transition
- Easy A/B testing and gradual rollout
- Simple rollback to legacy version if needed
- No complex branching/merging strategies

#### 5. Development Workflow
- Modern repo can use modern CI/CD practices from day one
- No need to maintain backward compatibility with old tooling
- Developers work in clean environment
- Easier onboarding for new team members

## Repository Structure Options

### Option A: Completely New Repository (RECOMMENDED)

**Structure:**
```
Scotchmcdonald/freescout              # Legacy (current repo)
├── Laravel 5.5 codebase
├── 269 overrides
└── Old documentation

Scotchmcdonald/freescout-modern       # New repository
├── Laravel 11 codebase
├── Clean architecture
├── Modern tooling
└── Migration documentation
```

**Advantages:**
- ✅ Cleanest separation
- ✅ No conflicts or confusion
- ✅ Independent versioning (v1.x vs v2.x)
- ✅ Clear distinction for users/contributors
- ✅ Can archive legacy repo when ready

**Process:**
1. Create new `freescout-modern` repository
2. Initialize with Laravel 11
3. Port business logic incrementally
4. Run both in parallel during transition
5. Eventually deprecate/archive legacy repo

### Option B: Long-Lived Branch in Same Repository

**Structure:**
```
Scotchmcdonald/freescout
├── main (or dist)           # Legacy Laravel 5.5
├── modernization            # Laravel 11 development
│   ├── Laravel 11 codebase
│   └── Modern architecture
└── feature/* branches       # Feature branches off modernization
```

**Advantages:**
- ✅ Single repository to manage
- ✅ Shared issue tracking
- ✅ Unified contributor base

**Disadvantages:**
- ❌ Risk of accidentally merging incompatible code
- ❌ Confusion about which branch to work on
- ❌ Complex merge conflicts if main evolves
- ❌ Both stacks in same repo causes tooling conflicts
- ❌ Harder to maintain separate CI/CD pipelines

**Process:**
1. Create `modernization` branch (orphan branch - no shared history)
2. Initialize Laravel 11 on that branch
3. Never merge to main - keep permanently separate
4. Eventually replace main with modernization content

### Option C: Monorepo with Subdirectories

**Structure:**
```
Scotchmcdonald/freescout
├── legacy/                  # Old Laravel 5.5 app
│   ├── app/
│   ├── composer.json
│   └── ...
├── modern/                  # New Laravel 11 app
│   ├── app/
│   ├── composer.json
│   └── ...
└── shared/                  # Shared documentation
    └── MIGRATION_GUIDE.md
```

**Disadvantages:**
- ❌ Composer/dependency conflicts
- ❌ Confused tooling (IDE, linters)
- ❌ Complex CI/CD setup
- ❌ Not a standard Laravel structure
- ❌ Messy git history

**NOT RECOMMENDED**

## Recommended Strategy: Option A (New Repository)

### Phase 1: Repository Setup (Week 1)

1. **Create New Repository**
   ```bash
   # On GitHub
   Create new repository: Scotchmcdonald/freescout-modern
   
   # Description: "Modern Laravel 11 version of FreeScout helpdesk"
   # Initialize with README
   ```

2. **Initialize Laravel 11**
   ```bash
   # Locally
   composer create-project laravel/laravel freescout-modern "11.*"
   cd freescout-modern
   git remote add origin git@github.com:Scotchmcdonald/freescout-modern.git
   git push -u origin main
   ```

3. **Initial Configuration**
   - Copy `.env.example` structure from legacy
   - Set up modern composer.json (from planning docs)
   - Configure Laravel Pint, Larastan, PHPUnit
   - Set up GitHub Actions for CI/CD

### Phase 2: Parallel Development (Weeks 2-12)

**Legacy Repository (freescout):**
- ✅ Remains operational
- ✅ Can receive critical bug fixes if needed
- ✅ Documentation points to modern version
- ✅ Eventually marked as "maintenance mode"

**Modern Repository (freescout-modern):**
- ✅ Active development happens here
- ✅ Port functionality incrementally (per roadmap)
- ✅ Weekly progress commits
- ✅ Full test coverage from start

### Phase 3: Transition (Week 13+)

1. **Deployment:**
   - Deploy modern version to staging
   - Run both versions in parallel
   - Gradual traffic cutover (10% → 50% → 100%)

2. **Repository Transition:**
   - Update legacy README to point to modern version
   - Add deprecation notice to legacy repo
   - Archive legacy repository (don't delete - keep for reference)

3. **Future:**
   - Modern repo becomes primary
   - New features only in modern version
   - Legacy repo available for historical reference

## Why NOT Use Branches in Current Repo?

### Technical Incompatibilities
1. **Composer conflicts**: Laravel 5.5 and 11.x have incompatible dependencies
2. **PHP version**: 7.1 vs 8.2 - fundamentally different
3. **Vendor directory**: 269 overrides vs clean vendor
4. **Asset pipeline**: Webpack Mix vs Vite

### Practical Problems
```bash
# Switching branches would require:
composer install    # Different dependencies
php artisan ...     # Different Laravel version
npm install         # Different frontend tools
php --version       # Different PHP requirements

# This is impractical for daily development
```

## Migration Bridge

To connect the repositories during development:

### Shared Documentation
Keep migration documentation in **both** repositories:
- Legacy repo: Points developers to modern version
- Modern repo: Contains full migration history

### Data Migration Scripts
Create in **modern** repository:
```
freescout-modern/
├── database/
│   ├── migrations/          # New schema
│   └── legacy-migration/    # Scripts to import from v1
│       ├── import-users.php
│       ├── import-tickets.php
│       └── import-conversations.php
```

### API Compatibility Layer (if needed)
If systems need to run in parallel:
```
freescout-modern/
├── app/
│   └── Services/
│       └── LegacyBridge/    # Read-only access to legacy DB
```

## Recommended Timeline

### Week 1: Repository Setup
- [ ] Create `Scotchmcdonald/freescout-modern` repository
- [ ] Initialize Laravel 11
- [ ] Set up modern tooling (Pint, Larastan, PHPUnit)
- [ ] Configure CI/CD pipeline
- [ ] Add initial README explaining relationship to legacy

### Week 2: Foundation
- [ ] Configure modern composer.json
- [ ] Set up database migrations
- [ ] Initialize module system (without license auth)
- [ ] Create basic application structure

### Weeks 3-12: Incremental Porting
- [ ] Port functionality per IMPLEMENTATION_ROADMAP.md
- [ ] Regular commits to modern repository
- [ ] Weekly progress reports
- [ ] Legacy repo remains untouched (except critical fixes)

### Week 13+: Transition
- [ ] Deploy modern version
- [ ] Parallel running
- [ ] Data migration
- [ ] Deprecate legacy repository

## Answer to "Should this start in a new repo/branch?"

### Short Answer: **New Repository**

Create `Scotchmcdonald/freescout-modern` as a completely separate repository.

### Rationale:
1. ✅ **Technical necessity**: Laravel 5.5 and 11.x cannot coexist
2. ✅ **Clean development**: No conflicts or confusion
3. ✅ **Better git history**: Fresh start with modern practices
4. ✅ **Flexible deployment**: Both versions can run in parallel
5. ✅ **Clear transition path**: Gradual migration with rollback option

### Implementation Steps:

**Immediate (This Week):**
1. Create new GitHub repository: `Scotchmcdonald/freescout-modern`
2. Initialize with Laravel 11: `composer create-project laravel/laravel freescout-modern "11.*"`
3. Add README explaining it's the modernized version
4. Link from legacy repo README to modern repo

**Next Phase:**
1. Follow IMPLEMENTATION_ROADMAP.md week by week
2. Port functionality to modern repository
3. Maintain legacy repository in read-only/maintenance mode
4. Eventually archive legacy when transition is complete

## Branch Strategy Within Modern Repository

Once `freescout-modern` is created, use standard Git Flow:

```
main                    # Production-ready code
├── develop            # Integration branch
    ├── feature/auth           # Feature branches
    ├── feature/email-system
    ├── feature/tickets
    └── feature/modules
```

**Workflow:**
1. Create feature branches off `develop`
2. Merge features to `develop` after testing
3. Release from `develop` to `main`
4. Tag releases: `v2.0.0`, `v2.1.0`, etc.

## Conclusion

**Create a new repository** (`Scotchmcdonald/freescout-modern`) for the Laravel 11 modernization. This provides the cleanest separation, avoids technical conflicts, and enables a smooth transition with minimal risk.

The legacy repository (`Scotchmcdonald/freescout`) remains as a reference and for any critical maintenance, eventually being archived once the modern version is fully deployed.

---

**Next Steps:**
1. Approve this strategy
2. Create `freescout-modern` repository
3. Initialize Laravel 11
4. Begin Week 1 tasks from IMPLEMENTATION_ROADMAP.md
