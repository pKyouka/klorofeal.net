<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WorkspaceService
{
    /**
     * Return the workspace currently bound in the IoC container (set by middleware).
     */
    public static function current(): ?Workspace
    {
        return app()->bound('workspace') ? app('workspace') : null;
    }

    /**
     * Provision a brand-new workspace for a paying customer.
     *
     * Creates the workspace, an admin user, and optionally extra roles.
     * After this call the workspace is ready for the customer to log in.
     *
     * Example usage from artisan / controller:
     *   app(WorkspaceService::class)->provision('Toko ABC', 'admin@tokoabc.com', 'secret123');
     */
    public function provision(
        string $workspaceName,
        string $adminEmail,
        string $adminPassword,
        string $adminName = ''
    ): Workspace {
        return DB::transaction(function () use ($workspaceName, $adminEmail, $adminPassword, $adminName) {
            $slug = Str::slug($workspaceName) . '-' . Str::lower(Str::random(6));

            $workspace = Workspace::create([
                'name'    => $workspaceName,
                'slug'    => $slug,
                'is_demo' => false,
            ]);

            // Bind workspace so the BelongsToWorkspace auto-assign fires correctly.
            app()->instance('workspace', $workspace);

            $adminRole = Role::where('name', 'admin')->first();

            $user = User::create([
                'name'         => $adminName ?: ('Admin ' . $workspaceName),
                'email'        => $adminEmail,
                'password'     => Hash::make($adminPassword),
                'workspace_id' => $workspace->id,
                'is_demo'      => false,
            ]);

            if ($adminRole) {
                $user->roles()->attach($adminRole->id);
            }

            return $workspace;
        });
    }
}
