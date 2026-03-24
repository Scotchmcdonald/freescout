<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Attachment;
use Tests\PureUnitTestCase;

class AttachmentTest extends PureUnitTestCase
{
    public function test_human_file_size_formats_bytes_into_expected_units(): void
    {
        $bytes = new Attachment(['file_size' => 500]);
        $kilobytes = new Attachment(['file_size' => 1536]);
        $megabytes = new Attachment(['file_size' => 5 * 1024 * 1024]);

        $this->assertSame('500 B', $bytes->human_file_size);
        $this->assertSame('1.5 KB', $kilobytes->human_file_size);
        $this->assertSame('5 MB', $megabytes->human_file_size);
    }

    public function test_is_image_checks_mime_prefix_and_handles_null_mime_type(): void
    {
        $image = new Attachment(['mime_type' => 'image/png']);
        $text = new Attachment(['mime_type' => 'text/plain']);
        $unknown = new Attachment(['mime_type' => null]);

        $this->assertTrue($image->isImage());
        $this->assertFalse($text->isImage());
        $this->assertFalse($unknown->isImage());
    }

    public function test_human_file_size_rounds_with_two_decimal_precision_for_fractional_units(): void
    {
        $attachment = new Attachment(['file_size' => 1537]);

        $this->assertSame('1.5 KB', $attachment->human_file_size);
    }
}
