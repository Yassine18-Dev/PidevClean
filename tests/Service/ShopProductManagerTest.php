<?php

namespace App\Tests\Service;

use App\Entity\ShopProduct;
use App\Entity\Size;
use App\Service\ShopProductManager;
use PHPUnit\Framework\TestCase;

class ShopProductManagerTest extends TestCase
{
    public function testValidProduct()
    {
        $product = new ShopProduct();
        $product->setName('Produit Test');
        $product->setPrice(50);
        $product->setType('skin');

        $manager = new ShopProductManager();

        $this->assertTrue($manager->validate($product));
    }

    public function testProductWithoutName()
    {
        $this->expectException(\InvalidArgumentException::class);

        $product = new ShopProduct();
        $product->setPrice(50);
        $product->setType('skin');

        $manager = new ShopProductManager();
        $manager->validate($product);
    }

    public function testProductWithNegativePrice()
    {
        $this->expectException(\InvalidArgumentException::class);

        $product = new ShopProduct();
        $product->setName('Produit Test');
        $product->setPrice(-10);
        $product->setType('skin');

        $manager = new ShopProductManager();
        $manager->validate($product);
    }

    public function testMerchWithoutSize()
    {
        $this->expectException(\InvalidArgumentException::class);

        $product = new ShopProduct();
        $product->setName('T-shirt');
        $product->setPrice(30);
        $product->setType('merch');

        $manager = new ShopProductManager();
        $manager->validate($product);
    }

    public function testMerchWithSize()
    {
        $product = new ShopProduct();
        $product->setName('T-shirt');
        $product->setPrice(30);
        $product->setType('merch');

        $size = new Size();
        $product->addSize($size);

        $manager = new ShopProductManager();

        $this->assertTrue($manager->validate($product));
    }
}

