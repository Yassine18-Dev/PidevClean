<?php

namespace App\Service;

use App\Entity\ShopProduct;

class ShopProductManager
{
    public function validate(ShopProduct $product): bool
    {
        if (empty($product->getName())) {
            throw new \InvalidArgumentException('Le nom du produit est obligatoire');
        }

        if ($product->getPrice() <= 0) {
            throw new \InvalidArgumentException('Le prix doit être positif');
        }

        if (!in_array($product->getType(), ['skin', 'merch'])) {
            throw new \InvalidArgumentException('Type invalide');
        }

        if ($product->getType() === 'merch' && $product->getSizes()->isEmpty()) {
            throw new \InvalidArgumentException('Un produit merch doit avoir au moins une taille');
        }

        return true;
    }
}