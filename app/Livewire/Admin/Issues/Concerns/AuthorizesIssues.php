<?php

namespace App\Livewire\Admin\Issues\Concerns;

use App\Models\Concerns\HasIssues;
use Illuminate\Database\Eloquent\Model;

trait AuthorizesIssues
{
    protected function canViewIssues(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->hasRole('superadmin') || $user?->can('issue.view') || $user?->can('issue.manage'));
    }

    protected function canManageIssues(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->hasRole('superadmin') || $user?->can('issue.manage'));
    }

    /** The model an issue is about, from a morph type + id — only models that use HasIssues. */
    protected function resolveIssueSubject(?string $type, ?int $id): ?Model
    {
        if (! $type || ! $id) {
            return null;
        }

        $class = Model::getActualClassNameForMorph($type);

        if (! class_exists($class) || ! in_array(HasIssues::class, class_uses_recursive($class), true)) {
            return null;
        }

        return $class::find($id);
    }
}
