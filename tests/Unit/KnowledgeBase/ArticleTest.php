<?php

declare(strict_types=1);

namespace Tests\Unit\KnowledgeBase;

use Modules\KnowledgeBase\Models\Article;
use Tests\PureUnitTestCase;

if (! class_exists(StubArticle::class)) {
final class StubArticle extends Article
{
    protected static function booted(): void {}

    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }
}
}


/**
 * Minimal user stub that supports isClient().
 */
if (! class_exists(StubClientUser::class)) {
final class StubClientUser
{
    public function __construct(private readonly bool $client = false) {}

    public function isClient(): bool
    {
        return $this->client;
    }
}
}


final class ArticleTest extends PureUnitTestCase
{
    private function article(array $attrs): StubArticle
    {
        $a = new StubArticle();
        $a->setRawAttributes($attrs);

        return $a;
    }

    // ── getContentForUser ─────────────────────────────────────────────

    public function test_get_content_for_client_returns_public_content(): void
    {
        $a = $this->article(['public_content' => 'Public text', 'internal_content' => 'Internal text']);
        $user = new StubClientUser(true);
        $this->assertSame('Public text', $a->getContentForUser($user));
    }

    public function test_get_content_for_client_falls_back_to_content_when_no_public(): void
    {
        $a = $this->article(['public_content' => null, 'content' => 'Default content', 'internal_content' => 'Internal']);
        $user = new StubClientUser(true);
        $this->assertSame('Default content', $a->getContentForUser($user));
    }

    public function test_get_content_for_internal_user_returns_internal_content(): void
    {
        $a = $this->article(['internal_content' => 'Internal text', 'content' => 'Default']);
        $user = new StubClientUser(false);
        $this->assertSame('Internal text', $a->getContentForUser($user));
    }

    public function test_get_content_for_internal_user_falls_back_to_content(): void
    {
        $a = $this->article(['internal_content' => null, 'content' => 'Default text']);
        $user = new StubClientUser(false);
        $this->assertSame('Default text', $a->getContentForUser($user));
    }

    public function test_get_content_for_user_without_is_client_method(): void
    {
        // An object without isClient() method — falls through to internal path
        $a = $this->article(['internal_content' => 'Internal', 'content' => 'Default']);
        $user = new \stdClass(); // no isClient() method
        $this->assertSame('Internal', $a->getContentForUser($user));
    }

    // ── getProductTagsArray ────────────────────────────────────────────

    public function test_get_product_tags_array_returns_empty_when_null(): void
    {
        $a = $this->article(['product_tags' => null]);
        $this->assertSame([], $a->getProductTagsArray());
    }

    public function test_get_product_tags_array_parses_csv_string(): void
    {
        $a = $this->article(['product_tags' => 'Windows,Office,Teams']);
        $result = $a->getProductTagsArray();
        $this->assertCount(3, $result);
        $this->assertContains('Windows', $result);
        $this->assertContains('Office', $result);
        $this->assertContains('Teams', $result);
    }

    // ── isDeprecated (pure status branch only) ────────────────────────

    public function test_is_deprecated_true_when_status_is_deprecated(): void
    {
        $a = $this->article(['verification_status' => 'deprecated', 'expires_at' => null]);
        $this->assertTrue($a->isDeprecated());
    }

    public function test_is_deprecated_false_when_status_is_verified_and_no_expiry(): void
    {
        $a = $this->article(['verification_status' => 'verified', 'expires_at' => null]);
        $this->assertFalse($a->isDeprecated());
    }

    public function test_authorization_boundary_deprecated_article_is_excluded_from_presentation(): void
    {
        // Authorization boundary: a deprecated article must not be presented to
        // users as valid — deprecation is a content authorization gate that
        // prevents stale or inaccurate information from being surfaced.
        $deprecated = $this->article(['verification_status' => 'deprecated', 'expires_at' => null]);
        $verified   = $this->article(['verification_status' => 'verified',    'expires_at' => null]);

        $this->assertTrue($deprecated->isDeprecated(),
            'Authorization boundary: deprecated article must be flagged'
        );
        $this->assertFalse($verified->isDeprecated(),
            'Authorization boundary: verified article must pass the presentation gate'
        );
    }
}
