<?php

namespace App\Traits;

use App\Models\Workspace;
use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Add this trait to any Eloquent model that belongs to a workspace.
 *
 * Effects:
 *  1. Adds a global WorkspaceScope — all queries are automatically filtered
 *     to the current workspace when one is bound in the IoC container.
 *  2. Auto-sets workspace_id on creation when workspace is bound.
 *  3. Exposes a workspace() BelongsTo relationship.
 *  4. Provides withoutWorkspace() for queries that must bypass tenant isolation
 *     (e.g. Midtrans webhook callbacks).
 */
trait BelongsToWorkspace
{
    public static function bootBelongsToWorkspace(): void
    {
        static::addGlobalScope(new WorkspaceScope);

        static::creating(function (self $model) {
            if (empty($model->workspace_id) && app()->bound('workspace')) {
                $model->workspace_id = app('workspace')->id;
            }
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Return a query builder with the workspace scope removed.
     * Use only for system-level operations that span all tenants.
     */
    public static function withoutWorkspace(): Builder
    {
        return static::withoutGlobalScope(WorkspaceScope::class);
    }
}
