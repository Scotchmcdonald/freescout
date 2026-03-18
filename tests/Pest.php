<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

pest()->extend(Tests\IntegrationTestCase::class)
    ->in('Integration');

pest()->extend(Tests\UnitTestCase::class)
    ->in('Unit');

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->group('browser')
    ->in('Browser');

pest()->group('browser')
    ->in('../Modules/*/Tests/Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

expect()->extend('toBeModel', function () {
    return $this->toBeInstanceOf(\Illuminate\Database\Eloquent\Model::class);
});

/**
 * Submit whichever login button variant is rendered by the view.
 */
function browserSubmitLoginForm(object $page): void
{
    try {
        $page->press('Log in');

        return;
    } catch (\Throwable $e) {
        // Fall through to other login button variants.
    }

    try {
        $page->press('Sign in');

        return;
    } catch (\Throwable $e) {
        // Fall through to a generic submit label.
    }

    $page->click('button[type="submit"]');
}

/**
 * Browser login helper for admin auth routes.
 */
function browserLoginAdmin(object $browser, \App\Models\User $user, string $password = 'password'): void
{
    if (method_exists($user, 'isAdmin') && ! $user->isAdmin()) {
        $user->role = \App\Models\User::ROLE_ADMIN;
        $user->save();
    }

    $page = $browser->visit('/login')
        ->assertVisible('input[name="email"]')
        ->assertVisible('input[name="password"]')
        ->type('email', $user->email)
        ->type('password', $password);

    browserSubmitLoginForm($page);

    try {
        $browser->waitForText('Dashboard', 10);
    } catch (\Throwable $e) {
        // Some pages do not show the literal dashboard title after login.
    }
}

/**
 * Browser login helper for client portal auth routes.
 */
function browserLoginPortal(object $browser, \App\Models\User $user, string $password = 'password'): void
{
    $page = $browser->visit('/portal/login')
        ->assertVisible('input[name="email"]')
        ->assertVisible('input[name="password"]')
        ->type('email', $user->email)
        ->type('password', $password);

    browserSubmitLoginForm($page);

    try {
        $browser->waitForText('Client Portal', 10);
    } catch (\Throwable $e) {
        // Fallback for pages that don't render this heading immediately.
    }
}

/*
|--------------------------------------------------------------------------
| Test Groups
|--------------------------------------------------------------------------
|
| Define test groups for selective execution and parallel worker optimization.
| Use `php artisan test --group=<group-name>` to run specific groups.
| The group() method is defined via pest()->group() in existing class definitions.
|
*/

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
