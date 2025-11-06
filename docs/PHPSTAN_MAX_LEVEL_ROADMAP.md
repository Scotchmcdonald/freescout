# PHPStan Max Level Roadmap
**Goal**: Achieve PHPStan Level 9 (Maximum Strictness)  
**Current**: Level 6 with 45 errors  
**Timeline**: 2-3 days of focused work

## 📊 PHPStan Levels Explained

| Level | Description | Effort | Typical Errors |
|-------|-------------|--------|----------------|
| 0 | Basic checks, unknown classes/functions | Easy | 0-10 |
| 1 | Unknown methods, properties on undefined variables | Easy | 10-20 |
| 2 | Unknown methods on all expressions, magic methods | Easy | 20-40 |
| 3 | Return types, types assigned to properties | Medium | 40-80 |
| 4 | Basic dead code, unreachable code | Medium | 60-100 |
| 5 | Checks for possibly undefined variables | Medium | 80-120 |
| **6** | **Currently here - Type checking** | **Medium** | **45** |
| 7 | Reports partially wrong union types | Hard | 100-200 |
| 8 | Reports calling methods/accessing properties on nullable types | Hard | 150-300 |
| 9 | **Maximum - Strictest checks, pure functions** | **Very Hard** | **200-500** |

## 🎯 Current Status: Level 6 (45 Errors)

### Error Breakdown
1. **Missing Return Types**: 7 errors (15%)
2. **Undefined Properties**: 5 errors (11%)
3. **Module Facade Calls**: 3 errors (7%)
4. **Type Specifications**: 15 errors (33%)
5. **Property Access**: 10 errors (22%)
6. **Miscellaneous**: 5 errors (12%)

## 📋 Phase-by-Phase Roadmap

### **Phase 1: Clean Level 6 (1 day)**
**Goal**: Fix all 45 current errors  
**Difficulty**: Medium  
**Expected Time**: 6-8 hours

#### Step 1.1: Add Missing Return Types (1-2 hours)
```php
// Before:
public function handle()
{
    // ...
}

// After:
public function handle(): int
{
    // ...
    return Command::SUCCESS;
}
```

**Files to Fix**:
- `app/Console/Commands/TestEventSystem.php` - add `: int`
- `app/Http/Controllers/ModulesController.php` - 4 methods need return types
- `app/Mail/AutoReply.php` - add `: self` or `: AutoReply`
- `app/Models/ActivityLog.php` - 3 scope methods need `: void`

#### Step 1.2: Fix Undefined Property Access (2 hours)
```php
// Before:
$attachment->file_dir . '/' . $attachment->file_name

// After - Option 1: Add properties to model
protected $fillable = [..., 'file_dir', 'file_name'];

// After - Option 2: Use accessor
public function getFullPath(): string
{
    return $this->getAttribute('file_dir') . '/' . $this->getAttribute('file_name');
}
```

**Files to Fix**:
- `app/Models/Attachment.php` - file_dir, file_name, file_size
- `app/Models/Channel.php` - active property
- `app/Models/SendLog.php` - opens, clicks properties

#### Step 1.3: Add Type Hints to ImapService (2-3 hours)
```php
// Before:
public function processMessage($message)

// After:
use Webklex\PHPIMAP\Message;

public function processMessage(Message $message): ?Thread
{
    // ...
}
```

**Files to Fix**:
- `app/Services/ImapService.php` - 10+ methods need type hints

#### Step 1.4: Fix Module Facade Calls (30 min)
```php
// Before:
Module::findByAlias($alias)

// After:
/** @var \Nwidart\Modules\Module|null $module */
$module = Module::findByAlias($alias);
```

Or use proper type checking:
```php
use Nwidart\Modules\Facades\Module;

$module = Module::find($alias);
if ($module) {
    // ...
}
```

#### Step 1.5: Fix Miscellaneous Issues (1 hour)
- Fix `ActivityLog` user relation
- Fix collection query optimization warnings
- Fix nullable offset access

**Result**: Level 6 with 0 errors ✅

---

### **Phase 2: Level 7 - Union Type Checks (4-6 hours)**
**Goal**: Pass level 7 strictness  
**Difficulty**: Hard  
**New Errors Expected**: 50-100

#### What Level 7 Adds:
- Reports calling methods with incorrect union types
- Checks for incompatible types in PHPDoc
- Validates template types in generics

#### Common Level 7 Issues:

**Issue 1: Union Types in Returns**
```php
// Level 7 Error:
public function find(int $id): User|null
{
    return User::where('id', $id)->first(); // Could return Model|null
}

// Fix:
public function find(int $id): ?User
{
    /** @var User|null $user */
    $user = User::where('id', $id)->first();
    return $user;
}
```

**Issue 2: Collection Generic Types**
```php
// Level 7 Error:
public function getUsers(): Collection
{
    return User::all(); // Collection<int, User> expected
}

// Fix:
/** @return \Illuminate\Database\Eloquent\Collection<int, User> */
public function getUsers(): Collection
{
    return User::all();
}
```

**Issue 3: Mixed Array Types**
```php
// Level 7 Error:
public function getData(): array
{
    return ['user' => $user, 'count' => 5]; // Mixed array
}

// Fix:
/** @return array{user: User, count: int} */
public function getData(): array
{
    return ['user' => $user, 'count' => 5];
}
```

#### Files Likely Affected:
- All Controllers (return types, request validation)
- All Models (relation return types)
- All Services (method signatures)
- Repositories (query builder return types)

**Estimated Fixes**: 50-100 errors

---

### **Phase 3: Level 8 - Nullable Type Strictness (6-8 hours)**
**Goal**: Pass level 8 strictness  
**Difficulty**: Very Hard  
**New Errors Expected**: 80-150

#### What Level 8 Adds:
- Reports calling methods on nullable types without null checks
- Validates property access on potentially null values
- Checks for undefined array keys

#### Common Level 8 Issues:

**Issue 1: Nullable Method Calls**
```php
// Level 8 Error:
public function show(int $id): View
{
    $user = User::find($id);
    return view('user', ['name' => $user->name]); // $user might be null
}

// Fix:
public function show(int $id): View
{
    $user = User::findOrFail($id); // Throws 404 if not found
    return view('user', ['name' => $user->name]);
}

// Or with null check:
public function show(int $id): View
{
    $user = User::find($id);
    if ($user === null) {
        abort(404);
    }
    return view('user', ['name' => $user->name]);
}
```

**Issue 2: Nullable Property Access**
```php
// Level 8 Error:
$conversation = Conversation::find($id);
$mailboxName = $conversation->mailbox->name; // Both could be null

// Fix:
$conversation = Conversation::with('mailbox')->findOrFail($id);
$mailboxName = $conversation->mailbox?->name ?? 'Unknown';
```

**Issue 3: Array Key Access**
```php
// Level 8 Error:
$data = json_decode($json, true);
$userId = $data['user_id']; // Key might not exist

// Fix:
$data = json_decode($json, true);
$userId = $data['user_id'] ?? null;
```

#### Files Heavily Affected:
- All Controllers (null checks everywhere)
- Services (defensive programming)
- Models (relation null checks)
- Helpers (array access)

**Estimated Fixes**: 80-150 errors

---

### **Phase 4: Level 9 - Maximum Strictness (8-12 hours)**
**Goal**: Pass level 9 - the maximum  
**Difficulty**: Extreme  
**New Errors Expected**: 100-200

#### What Level 9 Adds:
- Be strict about mixed types
- Reports possibly undefined variables
- Validates pure functions
- Checks for variable shadowing
- Reports implicit conversions

#### Common Level 9 Issues:

**Issue 1: Mixed Types Not Allowed**
```php
// Level 9 Error:
public function process(mixed $data): mixed // Too loose
{
    return $data;
}

// Fix: Be specific
/** @template T */
public function process(string|int|array $data): string|int|array
{
    return $data;
}
```

**Issue 2: Possibly Undefined Variables**
```php
// Level 9 Error:
if ($condition) {
    $result = 'yes';
}
return $result; // $result might not be defined

// Fix:
$result = null;
if ($condition) {
    $result = 'yes';
}
return $result;
```

**Issue 3: Implicit Type Conversions**
```php
// Level 9 Error:
$count = "5";
return $count + 1; // String to int conversion

// Fix:
$count = "5";
return (int)$count + 1;
```

**Issue 4: Side Effects in Pure Functions**
```php
// Level 9 Warning:
/** @pure */
public function calculate(int $x): int
{
    Log::info('Calculating'); // Side effect in pure function
    return $x * 2;
}

// Fix: Remove @pure or remove side effect
public function calculate(int $x): int
{
    return $x * 2;
}
```

#### Files Requiring Significant Refactoring:
- ALL Controllers (strict typing throughout)
- ALL Services (pure function annotations)
- ALL Models (accessor/mutator typing)
- ALL Helpers (no implicit conversions)

**Estimated Fixes**: 100-200 errors

---

## 📊 Complete Timeline

| Phase | Level | Time | Cumulative | Difficulty | Errors |
|-------|-------|------|------------|------------|--------|
| Current | 6 | - | - | Medium | 45 |
| Phase 1 | 6 → 0 errors | 6-8h | 1 day | Medium | 0 |
| Phase 2 | 7 | 4-6h | 1.5 days | Hard | 50-100 |
| Phase 3 | 8 | 6-8h | 2.5 days | Very Hard | 80-150 |
| Phase 4 | 9 | 8-12h | 4 days | Extreme | 100-200 |
| **Total** | **6→9** | **24-34h** | **4-5 days** | **Progressive** | **~0** |

## 🎯 Recommended Approach

### Option A: Pragmatic (Recommended)
**Target**: Level 7  
**Time**: 1.5-2 days  
**Benefit**: 90% of value, 40% of effort

1. ✅ Fix all Level 6 errors (1 day)
2. ✅ Pass Level 7 (0.5-1 day)
3. ⏸️ Stop at Level 7 (good enough for most projects)

**Why Stop at 7?**
- Level 7 catches 90% of real bugs
- Level 8-9 are very time-consuming
- Diminishing returns on bug prevention

### Option B: Thoroughness
**Target**: Level 8  
**Time**: 2.5-3 days  
**Benefit**: 95% of value, 70% of effort

1. ✅ Fix all Level 6 errors (1 day)
2. ✅ Pass Level 7 (0.5-1 day)
3. ✅ Pass Level 8 (1-1.5 days)
4. ⏸️ Stop at Level 8

### Option C: Perfectionist
**Target**: Level 9 (Max)  
**Time**: 4-5 days  
**Benefit**: 100% strictness, significant effort

Only recommended if:
- Building a critical system (medical, financial)
- Have dedicated time for code quality
- Team is experienced with strict typing

## 🛠️ Tools & Configuration

### PHPStan Configuration Updates

#### For Level 7:
```neon
# phpstan.neon
parameters:
    level: 7
    checkUnionTypes: true
    checkBenevolentUnionTypes: true
```

#### For Level 8:
```neon
parameters:
    level: 8
    checkNullableProperties: true
    strictPropertyInitialization: true
```

#### For Level 9:
```neon
parameters:
    level: 9
    checkMissingIterableValueType: true
    checkGenericClassInNonGenericObjectType: true
    checkImplicitMixed: true
    polluteScopeWithLoopInitialAssignments: false
    polluteScopeWithAlwaysIterableForeach: false
```

### Helper Scripts

#### Progressive Testing:
```bash
# Test each level incrementally
for level in {6..9}; do
    echo "Testing level $level"
    ./vendor/bin/phpstan analyse --level=$level --error-format=table
    if [ $? -eq 0 ]; then
        echo "✅ Level $level passed!"
    else
        echo "❌ Level $level failed - fix errors and continue"
        break
    fi
done
```

## 📋 Next Steps

1. **Start with Phase 1** - Fix current Level 6 errors
2. **Run baseline** - Generate error baseline for Level 7
3. **Fix incrementally** - Don't jump levels
4. **Test continuously** - Run PHPStan after each fix
5. **Document decisions** - Some errors might be false positives

## 🎯 Success Metrics

| Metric | Level 6 | Level 7 | Level 8 | Level 9 |
|--------|---------|---------|---------|---------|
| Type Safety | Good | Very Good | Excellent | Perfect |
| Bug Prevention | 70% | 85% | 95% | 99% |
| Maintenance | Medium | Medium | Low | Very Low |
| Initial Effort | Medium | High | Very High | Extreme |
| False Positives | Low | Medium | High | Very High |

## 🚀 Recommended Path Forward

**Week 1**: 
- Day 1: Fix all Level 6 errors (Phase 1)
- Day 2-3: Achieve Level 7 (Phase 2)

**Week 2** (Optional):
- Day 4-5: Achieve Level 8 (Phase 3)

**Later** (If needed):
- Level 9 only if building mission-critical features

**My Recommendation**: **Target Level 7** for excellent quality without excessive effort.
