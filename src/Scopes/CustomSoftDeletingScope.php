<?php

namespace Rabnawazak1\CustomSoftDelete\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CustomSoftDeletingScope implements Scope
{
    protected array $extensions = ['WithTrashed', 'WithoutTrashed', 'OnlyTrashed'];

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where($model->getQualifiedIsDeletedColumn(), 0);
    }

    public function extend(Builder $builder): void
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }
    }

    protected function addWithTrashed(Builder $builder): void
    {
        $builder->macro('withTrashed', function (Builder $builder, bool $withTrashed = true) {
            if (!$withTrashed) return $builder->withoutTrashed();

            return $builder->withoutGlobalScope($this);
        });
    }

    protected function addWithoutTrashed(Builder $builder): void
    {
        $builder->macro('withoutTrashed', function (Builder $builder) {
            $model = $builder->getModel();
            return $builder
                ->withoutGlobalScope($this)
                ->where($model->getQualifiedIsDeletedColumn(), 0);
        });
    }

    protected function addOnlyTrashed(Builder $builder): void
    {
        $builder->macro('onlyTrashed', function (Builder $builder) {
            $model = $builder->getModel();
            return $builder
                ->withoutGlobalScope($this)
                ->where($model->getQualifiedIsDeletedColumn(), 1);
        });
    }
}