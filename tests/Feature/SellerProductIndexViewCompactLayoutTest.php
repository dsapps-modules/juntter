<?php

namespace Tests\Feature;

use Tests\TestCase;

class SellerProductIndexViewCompactLayoutTest extends TestCase
{
    public function test_the_products_index_view_uses_a_compact_text_layout(): void
    {
        $viewSource = file_get_contents(base_path('resources/views/seller/products/index.blade.php'));

        $this->assertIsString($viewSource);
        $this->assertStringContainsString('min-width: 0', $viewSource);
        $this->assertStringContainsString('text-overflow: ellipsis', $viewSource);
        $this->assertStringContainsString('white-space: nowrap', $viewSource);
        $this->assertStringContainsString('overflow: hidden', $viewSource);
    }
}
