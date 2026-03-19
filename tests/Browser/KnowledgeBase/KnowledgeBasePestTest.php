<?php

use App\Models\User;
use Modules\KnowledgeBase\Models\Article;
use Modules\KnowledgeBase\Models\Category;

function getKbAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'kb-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'KBAdmin',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    return $admin;
}

it('knowledge base index loads', function () {
    $admin = getKbAdmin();

    browserLoginAdmin($this, $admin);

    $this->visit('/knowledgebase')
        ->assertPathIs('/knowledgebase');
})->group('knowledgebase', 'kb-index');

it('admin can create article', function () {
    $admin = getKbAdmin();

    $category = Category::create([
        'name' => 'Getting Started',
        'slug' => 'getting-started-'.uniqid(),
        'description' => 'Introductory articles',
        'order' => 1,
    ]);

    browserLoginAdmin($this, $admin);

    $this->visit('/knowledgebase/articles/create')
        ->assertPathIs('/knowledgebase/articles/create');
})->group('knowledgebase', 'kb-article');

it('article show page renders', function () {
    $admin = getKbAdmin();

    $category = Category::create([
        'name' => 'Tutorials',
        'slug' => 'tutorials-'.uniqid(),
        'description' => 'Tutorial articles',
        'order' => 1,
    ]);

    $article = Article::create([
        'category_id' => $category->id,
        'title' => 'How to Configure Email',
        'slug' => 'how-to-configure-email-'.uniqid(),
        'content' => 'This guide explains how to configure email settings.',
        'excerpt' => 'Email configuration guide',
        'is_published' => true,
        'author_id' => $admin->id,
        'allowed_roles' => ['user_role:2', 'user_role:1'],
    ]);

    browserLoginAdmin($this, $admin);

    $this->visit("/knowledgebase/{$article->slug}")
        ->assertSee('How to Configure Email');
})->group('knowledgebase', 'kb-article');

it('admin can edit article', function () {
    $admin = getKbAdmin();

    $category = Category::firstOrCreate(
        ['slug' => 'faqs-edit-test'],
        ['name' => 'FAQs', 'slug' => 'faqs-edit-test', 'description' => 'Frequently asked questions', 'order' => 2]
    );

    $article = Article::firstOrCreate(
        ['slug' => 'edit-test-article'],
        [
            'category_id' => $category->id,
            'title' => 'Common Troubleshooting Steps',
            'slug' => 'edit-test-article',
            'content' => 'Steps for common troubleshooting scenarios.',
            'is_published' => true,
            'author_id' => $admin->id,
            'allowed_roles' => ['user_role:2', 'user_role:1'],
        ]
    );

    browserLoginAdmin($this, $admin);

    $this->visit("/knowledgebase/articles/{$article->id}/edit")
        ->assertPathIs("/knowledgebase/articles/{$article->id}/edit");
})->group('knowledgebase', 'kb-article');

it('feature explorer loads', function () {
    $admin = getKbAdmin();

    browserLoginAdmin($this, $admin);

    $this->visit('/knowledgebase/explore')
        ->assertPathIs('/knowledgebase/explore');
})->group('knowledgebase', 'kb-explore');

it('article model has required methods', function () {
    $admin = getKbAdmin();

    $category = Category::create([
        'name' => 'Model Test',
        'slug' => 'model-test-'.uniqid(),
        'description' => 'For model testing',
        'order' => 3,
    ]);

    $article = Article::create([
        'category_id' => $category->id,
        'title' => 'Test Article Methods',
        'slug' => 'test-article-methods-'.uniqid(),
        'content' => 'Article content for testing.',
        'is_published' => true,
        'author_id' => $admin->id,
    ]);

    // Verify the article was created
    expect($article->id)->toBeGreaterThan(0);
    expect($article->title)->toBe('Test Article Methods');
    expect($article->is_published)->toBeTrue();

    // Verify the category relationship
    expect($article->category)->not->toBeNull();
    expect($article->category->id)->toBe($category->id);

    // Verify the author relationship
    expect($article->author)->not->toBeNull();
    expect($article->author->id)->toBe($admin->id);

    // Verify category has articles
    expect($category->articles()->count())->toBe(1);
})->group('knowledgebase', 'kb-model');
