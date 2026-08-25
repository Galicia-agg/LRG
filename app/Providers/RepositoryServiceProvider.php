<?php

namespace App\Providers;

use App\Repositories\Contracts\CashSessionRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\CommonFailureRepositoryInterface;
use App\Repositories\Contracts\CommonServiceRepositoryInterface;
use App\Repositories\Contracts\CustomerVehicleRepositoryInterface;
use App\Repositories\Contracts\MechanicRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\QuoteRepositoryInterface;
use App\Repositories\Contracts\SaleRepositoryInterface;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use App\Repositories\Eloquent\CashSessionRepository;
use App\Repositories\Eloquent\CategoryRepository;
use App\Repositories\Eloquent\CustomerRepository;
use App\Repositories\Eloquent\CommonFailureRepository;
use App\Repositories\Eloquent\CommonServiceRepository;
use App\Repositories\Eloquent\CustomerVehicleRepository;
use App\Repositories\Eloquent\MechanicRepository;
use App\Repositories\Eloquent\OrderRepository;
use App\Repositories\Eloquent\ProductRepository;
use App\Repositories\Eloquent\QuoteRepository;
use App\Repositories\Eloquent\SaleRepository;
use App\Repositories\Eloquent\StockMovementRepository;
use App\Repositories\Eloquent\SupplierRepository;
use App\Repositories\Eloquent\WorkOrderRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $repositoryBindings = [
        ProductRepositoryInterface::class => ProductRepository::class,
        CategoryRepositoryInterface::class => CategoryRepository::class,
        SupplierRepositoryInterface::class => SupplierRepository::class,
        CustomerRepositoryInterface::class => CustomerRepository::class,
        StockMovementRepositoryInterface::class => StockMovementRepository::class,
        CashSessionRepositoryInterface::class => CashSessionRepository::class,
        SaleRepositoryInterface::class => SaleRepository::class,
        OrderRepositoryInterface::class => OrderRepository::class,
        QuoteRepositoryInterface::class => QuoteRepository::class,
        CustomerVehicleRepositoryInterface::class => CustomerVehicleRepository::class,
        WorkOrderRepositoryInterface::class => WorkOrderRepository::class,
        MechanicRepositoryInterface::class => MechanicRepository::class,
        CommonFailureRepositoryInterface::class => CommonFailureRepository::class,
        CommonServiceRepositoryInterface::class => CommonServiceRepository::class,
    ];

    public function register(): void
    {
        foreach ($this->repositoryBindings as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }
}
