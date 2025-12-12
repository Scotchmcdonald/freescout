You are an expert Laravel developer tasked with modernizing a FreeScout module for Laravel 11.

Please perform the following modernization steps on the module located in `Modules/[ModuleName]`:

1.  **Namespace Updates**:
    *   Scan all PHP files in the module.
    *   Replace references to root models (e.g., `App\User`) with `App\Models\User`.
    *   Common models to check: `Conversation`, `Customer`, `Email`, `User`, `Mailbox`, `Thread`, `Folder`, `Attachment`.

2.  **Route Modernization**:
    *   Open `Http/routes.php`.
    *   Refactor `Route::group` to remove the `namespace` attribute.
    *   Import the module's controllers at the top of the file.
    *   Convert string-based route definitions (e.g., `'Controller@method'`) to tuple syntax (e.g., `[Controller::class, 'method']`).

3.  **Migration Updates**:
    *   Check all files in `Database/Migrations`.
    *   Replace `$table->increments('id')` with `$table->id()`.
    *   Replace `$table->integer('...')->unsigned()` or `$table->unsignedInteger('...')` with `$table->unsignedBigInteger('...')` for foreign keys referencing core tables (users, mailboxes, conversations, etc.).

4.  **Controller Updates**:
    *   Check controllers in `Http/Controllers`.
    *   Ensure they extend `App\Http\Controllers\Controller` instead of `Illuminate\Routing\Controller`.
    *   Replace `use Validator;` with `use Illuminate\Support\Facades\Validator;`.

5.  **Service Provider Cleanup**:
    *   Check `Providers/[Module]ServiceProvider.php`.
    *   Remove `registerFactories()` call in `boot()` and the method definition if it exists, as legacy factories are not supported in the same way.

Please apply these changes to the code.
