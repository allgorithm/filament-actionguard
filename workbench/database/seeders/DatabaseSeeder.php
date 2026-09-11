<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Workbench\App\Models\Category;
use Workbench\App\Models\Product;
use Workbench\App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create or update Admin User
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
            ]
        );

        // 2. Create Categories
        $electronics = Category::firstOrCreate(
            ['slug' => 'elektronik'],
            ['name' => 'Elektronik & Gadgets']
        );

        $media = Category::firstOrCreate(
            ['slug' => 'medien'],
            ['name' => 'Bücher & Medien']
        );

        // 3. Create Demo Products for ActionGuard Tests
        Product::updateOrCreate(
            ['name' => 'Draft Produkt (Unvollständig)'],
            [
                'sku' => null,
                'price' => null,
                'status' => 'draft',
                'image_url' => null,
                'category_id' => null,
                'description' => 'Dieses Produkt hat noch keine SKU, keinen Preis und kein Produktbild. ActionGuard blockiert hier komplett.',
            ]
        );

        Product::updateOrCreate(
            ['name' => 'Kopfhörer Pro (Fehlendes Bild)'],
            [
                'sku' => 'HP-9000-BLK',
                'price' => 149.99,
                'status' => 'draft',
                'image_url' => null,
                'category_id' => $electronics->id,
                'description' => 'Gepflegte Stammdaten, aber das Produktbild fehlt für den Release.',
            ]
        );

        Product::updateOrCreate(
            ['name' => 'Wireless Noise-Cancelling Headphones'],
            [
                'sku' => 'WNC-2026-X',
                'price' => 249.00,
                'status' => 'draft',
                'image_url' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500',
                'category_id' => $electronics->id,
                'description' => 'Vollständig gepflegtes Spitzenmodell. Alle ActionGuard-Prüfungen bestehen.',
            ]
        );

        Product::updateOrCreate(
            ['name' => 'Smartwatch Active 5'],
            [
                'sku' => 'SWA-500-SLV',
                'price' => 199.95,
                'status' => 'published',
                'image_url' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500',
                'category_id' => $electronics->id,
                'description' => 'Bereits veröffentlicht.',
            ]
        );
    }
}
