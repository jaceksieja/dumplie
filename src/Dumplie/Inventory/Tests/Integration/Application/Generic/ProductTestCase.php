<?php

declare (strict_types = 1);

namespace Dumplie\Inventory\Tests\Integration\Application\Generic;

use Dumplie\Inventory\Application\Command\CreateProduct;
use Dumplie\Inventory\Application\Command\PutBackProductToStock;
use Dumplie\Inventory\Application\Command\RemoveProductFromStock;
use Dumplie\Inventory\Domain\Product;
use Dumplie\SharedKernel\Domain\Money\Price;
use Dumplie\SharedKernel\Domain\Product\SKU;
use Dumplie\Inventory\Tests\InventoryContext;

abstract class ProductTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InventoryContext
     */
    protected $inventoryContext;

    abstract protected function clear();

    function test_creating_product()
    {
        $this->inventoryContext->commandBus()->handle(new CreateProduct(
            'dumplie-sku-1',
            20000,
            'EUR',
            false
        ));

        $this->clear();
        $product = $this->inventoryContext->products()->getBySku(new SKU('dumplie-sku-1'));
        $this->assertEquals(
            new Product(
                new SKU('dumplie-sku-1'),
                Price::fromInt(200, 'EUR'),
                false
            ),
            $product
        );
    }

    function test_that_removes_product_from_stock()
    {
        $this->inventoryContext->commandBus()
            ->handle(new CreateProduct(
                'dumplie-sku-1',
                20000,
                'EUR',
                true
            ));

        $this->inventoryContext->commandBus()
            ->handle(new RemoveProductFromStock('dumplie-sku-1'));

        $this->clear();
        $product = $this->inventoryContext->products()->getBySku(new SKU('dumplie-sku-1'));
        $this->assertEquals(
            new Product(
                new SKU('dumplie-sku-1'),
                Price::fromInt(200, 'EUR'),
                false
            ),
            $product
        );
    }

    function test_that_put_back_product_to_stock()
    {
        $this->inventoryContext->commandBus()->handle(new CreateProduct(
            'dumplie-sku-1',
            20050,
            'EUR',
            true
        ));

        $this->inventoryContext->commandBus()->handle(new RemoveProductFromStock('dumplie-sku-1'));
        $this->inventoryContext->commandBus()->handle(new PutBackProductToStock('dumplie-sku-1'));

        $this->clear();
        $product = $this->inventoryContext->products()->getBySku(new SKU('dumplie-sku-1'));
        $this->assertEquals(
            new Product(
                new SKU('dumplie-sku-1'),
                new Price(20050, 'EUR'),
                true
            ),
            $product
        );
    }
}