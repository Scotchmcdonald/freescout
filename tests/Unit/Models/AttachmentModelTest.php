<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Attachment;
use Tests\PureUnitTestCase;

final class TestAttachmentHelper extends Attachment
{
    protected function casts(): array
    {
        return [];
    }
}

class AttachmentModelTest extends PureUnitTestCase
{
    private function attachment(?string $mimeType): TestAttachmentHelper
    {
        $a = new TestAttachmentHelper;
        $a->mime_type = $mimeType;

        return $a;
    }

    public function test_is_image_returns_true_for_image_mime_types(): void
    {
        $this->assertTrue($this->attachment('image/jpeg')->isImage());
        $this->assertTrue($this->attachment('image/png')->isImage());
        $this->assertTrue($this->attachment('image/gif')->isImage());
        $this->assertTrue($this->attachment('image/webp')->isImage());
    }

    public function test_is_image_returns_false_for_non_image_mime_types(): void
    {
        $this->assertFalse($this->attachment('application/pdf')->isImage());
        $this->assertFalse($this->attachment('text/plain')->isImage());
        $this->assertFalse($this->attachment('video/mp4')->isImage());
    }

    public function test_is_image_returns_false_when_mime_type_is_null(): void
    {
        $this->assertFalse($this->attachment(null)->isImage());
    }
}
