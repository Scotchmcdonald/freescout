# Module Modernization Guide (Laravel 11)

This guide outlines the steps to modernize a FreeScout module for compatibility with the Laravel 11 Foundation.

## 1. Namespace & Model References
Old modules often reference models in the root `App\` namespace. These must be updated to `App\Models\`.

**Search & Replace:**
- `use App\Conversation;` -> `use App\Models\Conversation;`
- `use App\Customer;` -> `use App\Models\Customer;`
- `use App\Email;` -> `use App\Models\Email;`
- `use App\User;` -> `use App\Models\User;`
- `use App\Mailbox;` -> `use App\Models\Mailbox;`
- `use App\Thread;` -> `use App\Models\Thread;`
- `use App\Folder;` -> `use App\Models\Folder;`
- `use App\Attachment;` -> `use App\Models\Attachment;`

## 2. Route Definitions
Update `Http/routes.php` to use the modern tuple syntax `[Controller::class, 'method']` instead of string-based routing.

**Steps:**
1. Remove `namespace` attribute from `Route::group`.
2. Import the controller at the top of the file.
3. Convert routes.

**Example:**
```php
// Before
Route::group(['namespace' => 'Modules\Foo\Http\Controllers'], function() {
    Route::get('/foo', 'FooController@index');
});

// After
use Modules\Foo\Http\Controllers\FooController;

Route::group([], function() {
    Route::get('/foo', [FooController::class, 'index']);
});
```

## 3. Database Migrations
Ensure migrations use `bigIncrements` (or `id()`) and `unsignedBigInteger` for foreign keys to match Laravel 11 defaults.

**Changes:**
- `$table->increments('id');` -> `$table->id();`
- `$table->integer('user_id')->unsigned();` -> `$table->unsignedBigInteger('user_id');`
- `$table->unsignedInteger('mailbox_id');` -> `$table->unsignedBigInteger('mailbox_id');`

## 4. Controller Inheritance
Controllers should extend the application's base controller to inherit middleware and shared logic.

**Update:**
- `use Illuminate\Routing\Controller;` -> `use App\Http\Controllers\Controller;`
- Ensure class extends `Controller`.

## 5. Service Providers
- Remove calls to `registerFactories()` if the module does not use factories or if it uses the legacy factory system.
- Ensure `loadMigrationsFrom` points to the correct directory.

## 6. Validation
- Ensure `Validator` is imported via `use Illuminate\Support\Facades\Validator;` instead of relying on the alias.
