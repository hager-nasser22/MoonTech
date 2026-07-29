<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    // 1. اختبار عرض المنتجات (Public)
    public function test_can_fetch_products_list()
    {
        Product::create([
            'title' => 'Test Item',
            'price' => 100,
            'stock' => 5,
        ]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    // 2. اختبار منع غير الأدمن من إضافة منتج
    public function test_non_admin_cannot_create_product()
    {
        // إنشاء مستخدم عادي برقم تليفون بدون email
        $user = User::create([
            'name'     => 'Regular User',
            'phone'    => '01000000000',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/products', [
                'title' => 'Test Product',
                'price' => 100,
                'stock' => 10,
            ]);

        $response->assertStatus(403);
    }
}
