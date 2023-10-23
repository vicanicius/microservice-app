<?php

namespace App\Repositories;

use App\Repositories\Contracts\BaseRepositoryContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class BaseRepositoryEloquent implements BaseRepositoryContract
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * {@inheritDoc}
     */
    public function updateOrCreate(array $find, array $value): Model
    {
        return $this->model::updateOrCreate($find, $value);
    }

    public function searchTopScore(string $value): Collection
    {
        return $this->model::search("{$value}")->orderBy('_score', 'desc')->get();
    }

    /**
     * {@inheritDoc}
     */
    public function update(int|Model $model, array $attributes = [], array $options = []): bool
    {
        if ($model instanceof Model) {
            return $model->update($attributes, $options);
        }

        return $this->model::query()
            ->whereKey($model)
            ->update($attributes, $options);
    }
}
