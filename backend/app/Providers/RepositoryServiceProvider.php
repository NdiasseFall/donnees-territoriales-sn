<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Auth\Repositories\ApiKeyRepositoryInterface;
use App\Domain\Dataset\Repositories\DatasetRepositoryInterface;
use App\Domain\Territory\Repositories\SpatialQueryRepositoryInterface;
use App\Domain\Territory\Repositories\TerritoryRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentApiKeyRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentDatasetRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentTerritoryRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\PostgisSpatialRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider enregistrant les bindings des Repositories DDD.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            TerritoryRepositoryInterface::class,
            EloquentTerritoryRepository::class
        );

        $this->app->bind(
            SpatialQueryRepositoryInterface::class,
            PostgisSpatialRepository::class
        );

        $this->app->bind(
            DatasetRepositoryInterface::class,
            EloquentDatasetRepository::class
        );

        $this->app->bind(
            ApiKeyRepositoryInterface::class,
            EloquentApiKeyRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}
