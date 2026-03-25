<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\POS\Models\Sale;
use App\Modules\POS\Models\SaleItem;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductCategory;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Purchase\Models\PurchaseItem;
use App\Modules\Purchase\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class KlorofealDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $roles = Role::query()->pluck('id', 'name');

            // ----------------------------------------------------------------
            // Default workspace — the real client / operation accounts.
            // ----------------------------------------------------------------
            $defaultWorkspace = Workspace::updateOrCreate(
                ['slug' => 'klorofeal'],
                ['name' => 'Klorofeal', 'is_demo' => false]
            );

            app()->instance('workspace', $defaultWorkspace);

            $admin = User::updateOrCreate(
                ['email' => 'admin@klorofeal.test'],
                ['name' => 'Admin Klorofeal', 'password' => Hash::make('password'),
                 'workspace_id' => $defaultWorkspace->id, 'is_demo' => false]
            );
            $cashier = User::updateOrCreate(
                ['email' => 'cashier@klorofeal.test'],
                ['name' => 'Cashier Klorofeal', 'password' => Hash::make('password'),
                 'workspace_id' => $defaultWorkspace->id, 'is_demo' => false]
            );
            $warehouse = User::updateOrCreate(
                ['email' => 'warehouse@klorofeal.test'],
                ['name' => 'Warehouse Klorofeal', 'password' => Hash::make('password'),
                 'workspace_id' => $defaultWorkspace->id, 'is_demo' => false]
            );

            if (isset($roles['admin']))     $admin->roles()->syncWithoutDetaching([$roles['admin']]);
            if (isset($roles['cashier']))   $cashier->roles()->syncWithoutDetaching([$roles['cashier']]);
            if (isset($roles['warehouse'])) $warehouse->roles()->syncWithoutDetaching([$roles['warehouse']]);

            // Keep default admin workspace usable with starter data as well.
            $this->seedBusinessData($defaultWorkspace, $cashier);

            // ----------------------------------------------------------------
            // Demo workspace — only created when demo mode is enabled.
            // ----------------------------------------------------------------
            if (config('demo.enabled')) {
                $demoConfig = config('demo.account');

                $demoWorkspace = Workspace::updateOrCreate(
                    ['slug' => 'demo'],
                    ['name' => 'Demo Klorofeal', 'is_demo' => true]
                );

                app()->instance('workspace', $demoWorkspace);

                $demoUser = User::updateOrCreate(
                    ['email' => $demoConfig['email']],
                    ['name' => $demoConfig['name'], 'password' => Hash::make($demoConfig['password']),
                     'workspace_id' => $demoWorkspace->id, 'is_demo' => true]
                );

                if (isset($roles['admin'])) {
                    $demoUser->roles()->syncWithoutDetaching([$roles['admin']]);
                }

                $this->seedBusinessData($demoWorkspace, $demoUser);
            }
        });
    }

    /**
     * Seed (or re-seed) business data for the given workspace.
     *
     * Called by run() for initial setup and by DemoWorkspaceService::reset()
     * when a demo workspace is manually reset. Does NOT touch users.
     */
    public function seedBusinessData(Workspace $workspace, ?User $cashier): void
    {
        app()->instance('workspace', $workspace);

        // --- Product Categories ---
        $beverages = ProductCategory::updateOrCreate(
            ['slug' => 'beverages'],
            ['name' => 'Beverages', 'description' => 'All beverage products']
        );
        $snacks = ProductCategory::updateOrCreate(
            ['slug' => 'snacks'],
            ['name' => 'Snacks', 'description' => 'Packaged snack products']
        );

        // --- Products & Stocks ---
        $productData = [
            ['sku' => 'PRD-KOPI-001',  'barcode' => '8991000010001', 'name' => 'Kopi Botol 250ml',
             'category_id' => $beverages->id, 'cost_price' => 5500, 'sell_price' => 9000, 'stock' => 120, 'minimum_stock' => 20],
            ['sku' => 'PRD-TEH-001',   'barcode' => '8991000010002', 'name' => 'Teh Melati 300ml',
             'category_id' => $beverages->id, 'cost_price' => 4000, 'sell_price' => 7000, 'stock' => 90, 'minimum_stock' => 20],
            ['sku' => 'PRD-SNACK-001', 'barcode' => '8991000010003', 'name' => 'Keripik Singkong Original',
             'category_id' => $snacks->id,    'cost_price' => 3500, 'sell_price' => 6500, 'stock' => 35, 'minimum_stock' => 20],
        ];
        foreach ($productData as $payload) {
            Product::updateOrCreate(['sku' => $payload['sku']], $payload);
        }

        // --- Supplier ---
        $supplier = Supplier::updateOrCreate(
            ['name' => 'PT Sumber Retail Nusantara'],
            ['contact_person' => 'Ari Suryadi', 'phone' => '081234567890',
             'email' => 'procurement@sumberretail.co.id', 'address' => 'Jl. Perdagangan No. 15, Jakarta']
        );

        // --- Purchase ---
        $purchase = Purchase::firstOrCreate(
            ['invoice_number' => 'PO-20260314-0001'],
            ['supplier_id' => $supplier->id, 'total_amount' => 172500, 'purchased_at' => Carbon::now()->subDays(2)]
        );
        if ($purchase->wasRecentlyCreated) {
            $productA = Product::where('sku', 'PRD-KOPI-001')->firstOrFail();
            $productB = Product::where('sku', 'PRD-SNACK-001')->firstOrFail();
            foreach ([[$productA, 10, 5500], [$productB, 5, 3500]] as [$prod, $qty, $price]) {
                PurchaseItem::create(['purchase_id' => $purchase->id, 'product_id' => $prod->id, 'qty' => $qty, 'price' => $price]);
                $prod->increment('stock', $qty);
                StockMovement::create(['product_id' => $prod->id, 'type' => 'in', 'quantity' => $qty,
                                       'reference_type' => Purchase::class, 'reference_id' => $purchase->id]);
            }
        }

        // --- Sale ---
        $sale = Sale::firstOrCreate(
            ['invoice_number' => 'INV-20260314-0001'],
            ['user_id' => $cashier?->id, 'total_amount' => 25000, 'payment_method' => 'cash', 'sold_at' => Carbon::now()->subDay()]
        );
        if ($sale->wasRecentlyCreated) {
            $productA = Product::where('sku', 'PRD-KOPI-001')->firstOrFail();
            $productB = Product::where('sku', 'PRD-TEH-001')->firstOrFail();
            foreach ([[$productA, 1, 9000], [$productB, 2, 7000]] as [$prod, $qty, $price]) {
                SaleItem::create(['sale_id' => $sale->id, 'product_id' => $prod->id, 'qty' => $qty, 'price' => $price, 'subtotal' => $qty * $price]);
                $prod->decrement('stock', $qty);
                StockMovement::create(['product_id' => $prod->id, 'type' => 'out', 'quantity' => $qty,
                                       'reference_type' => Sale::class, 'reference_id' => $sale->id]);
            }
        }
    }
}
