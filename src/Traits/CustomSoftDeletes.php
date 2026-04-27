<?php

namespace Rabnawazak1\CustomSoftDelete\Traits;

use Illuminate\Support\Facades\Auth;
use Rabnawazak1\CustomSoftDelete\Scopes\CustomSoftDeletingScope;

trait CustomSoftDeletes
{
    // ── Boot ─────────────────────────────────────────────────────────────

    public static function bootCustomSoftDeletes(): void
    {
        static::addGlobalScope(new CustomSoftDeletingScope());

        // Intercept Eloquent's delete before it hits the DB
        static::deleting(function ($model) {
            // If someone calls forceDelete(), skip our logic
            if ($model->isForceDeleting()) return;

            $model->performCustomSoftDelete();
            return false; // Cancel Eloquent's default DELETE query
        });
    }

    // ── Core Delete / Restore / Force ─────────────────────────────────────

    protected function performCustomSoftDelete(): void
    {
        $time = $this->freshTimestamp();

        $this->setAttribute($this->getIsDeletedColumn(), 1);
        $this->setAttribute($this->getDeletedAtColumn(), $time);
        $this->setAttribute($this->getDeletedByColumn(), Auth::id());

        // Bypass all observers/scopes, write directly
        $this->newModelQuery()
            ->where($this->getKeyName(), $this->getKey())
            ->update([
                $this->getIsDeletedColumn() => 1,
                $this->getDeletedAtColumn()  => $time,
                $this->getDeletedByColumn()  => Auth::id(),
            ]);

        $this->syncOriginal();
        $this->fireModelEvent('customSoftDeleted', false);
    }

    public function restore(): bool
    {
        $this->setAttribute($this->getIsDeletedColumn(), 0);
        $this->setAttribute($this->getDeletedAtColumn(), null);
        $this->setAttribute($this->getDeletedByColumn(), null);

        $result = $this->newModelQuery()
            ->where($this->getKeyName(), $this->getKey())
            ->update([
                $this->getIsDeletedColumn() => 0,
                $this->getDeletedAtColumn()  => null,
                $this->getDeletedByColumn()  => null,
            ]);

        $this->syncOriginal();
        $this->fireModelEvent('restored', false);

        return (bool) $result;
    }

    public function forceDelete(): ?bool
    {
        $this->forceDeleting = true;
        $result = $this->delete(); // Now runs the actual DB DELETE
        $this->forceDeleting = false;

        return $result;
    }

    // ── Status Checks ─────────────────────────────────────────────────────

    public function isSoftDeleted(): bool
    {
        return (bool) $this->getAttribute($this->getIsDeletedColumn());
    }

    // Alias matching Laravel convention
    public function trashed(): bool
    {
        return $this->isSoftDeleted();
    }

    // ── Column Name Resolvers (overridable per-model) ──────────────────────

    public function getIsDeletedColumn(): string
    {
        return defined('static::IS_DELETED') ? static::IS_DELETED : 'is_deleted';
    }

    public function getDeletedAtColumn(): string
    {
        return defined('static::DELETED_AT') ? static::DELETED_AT : 'deleted_at';
    }

    public function getDeletedByColumn(): string
    {
        return defined('static::DELETED_BY') ? static::DELETED_BY : 'deleted_by';
    }

    public function getQualifiedIsDeletedColumn(): string
    {
        return $this->qualifyColumn($this->getIsDeletedColumn());
    }

    // ── Force Delete Guard ─────────────────────────────────────────────────

    protected bool $forceDeleting = false;

    public function isForceDeleting(): bool
    {
        return $this->forceDeleting;
    }
}