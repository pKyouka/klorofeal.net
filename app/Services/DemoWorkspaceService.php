<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\KlorofealDemoSeeder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DemoWorkspaceService
{
    public function isEnabled(): bool
    {
        return (bool) config('demo.enabled');
    }

    public function reset(): void
    {
        if (! $this->isEnabled()) {
            throw new NotFoundHttpException('Demo mode is disabled in this environment.');
        }

        $workspace = Workspace::where('is_demo', true)->firstOrFail();

        DB::transaction(function () use ($workspace) {
            $wid = $workspace->id;

            DB::table('qris_payments')->where('workspace_id', $wid)->delete();
            DB::table('stock_opname_items')->where('workspace_id', $wid)->delete();
            DB::table('stock_opnames')->where('workspace_id', $wid)->delete();
            DB::table('sale_items')->where('workspace_id', $wid)->delete();
            DB::table('sales')->where('workspace_id', $wid)->delete();
            DB::table('purchase_items')->where('workspace_id', $wid)->delete();
            DB::table('purchases')->where('workspace_id', $wid)->delete();
            DB::table('stock_movements')->where('workspace_id', $wid)->delete();
            DB::table('products')->where('workspace_id', $wid)->delete();
            DB::table('suppliers')->where('workspace_id', $wid)->delete();
            DB::table('product_categories')->where('workspace_id', $wid)->delete();

            $demoUser = User::where('workspace_id', $wid)->where('is_demo', true)->first();

            app()->instance('workspace', $workspace);
            app(KlorofealDemoSeeder::class)->seedBusinessData($workspace, $demoUser);
        }, 3);
    }
}
