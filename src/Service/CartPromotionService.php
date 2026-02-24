<?php

namespace App\Service;

use App\Entity\ShopProduct;
use App\Repository\PromotionRepository;

class CartPromotionService
{
    private PromotionRepository $promotionRepository;

    public function __construct(PromotionRepository $promotionRepository)
    {
        $this->promotionRepository = $promotionRepository;
    }

    /**
     * Calcule le total du panier avec les promotions
     */
    public function calculateCartTotal(array $cartItems): array
    {
        $totalOriginal = 0;
        $totalDiscount = 0;
        $totalFinal = 0;
        $appliedPromotions = [];

        foreach ($cartItems as $cartKey => $item) {
            // Gérer le cas où $item est directement un ShopProduct
            if ($item instanceof ShopProduct) {
                $product = $item;
                $quantity = 1; // Valeur par défaut si pas de quantité spécifiée
            } else {
                $product = $item['product'] ?? $item;
                $quantity = $item['quantity'] ?? 1;
            }
            
            $originalPrice = $product->getPrice();
            $finalPrice = $product->getFinalPrice();
            $discountAmount = $originalPrice - $finalPrice;
            
            $itemTotalOriginal = $originalPrice * $quantity;
            $itemTotalFinal = $finalPrice * $quantity;
            $itemTotalDiscount = $discountAmount * $quantity;
            
            $totalOriginal += $itemTotalOriginal;
            $totalFinal += $itemTotalFinal;
            $totalDiscount += $itemTotalDiscount;
            
            // Ajouter les promotions appliquées
            if ($product->hasActivePromotion()) {
                $bestPromotion = $product->getBestPromotion();
                $promotionKey = $bestPromotion->getId();
                
                if (!isset($appliedPromotions[$promotionKey])) {
                    $appliedPromotions[$promotionKey] = [
                        'promotion' => $bestPromotion,
                        'products' => [],
                        'total_discount' => 0,
                        'formatted_discount' => $bestPromotion->getFormattedValue()
                    ];
                }
                
                $appliedPromotions[$promotionKey]['products'][] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'discount' => $itemTotalDiscount
                ];
                
                $appliedPromotions[$promotionKey]['total_discount'] += $itemTotalDiscount;
                $appliedPromotions[$promotionKey]['product_count'] = count($appliedPromotions[$promotionKey]['products']);
                $appliedPromotions[$promotionKey]['discount'] = $bestPromotion->getFormattedValue();
            }
        }

        return [
            'total_original' => round($totalOriginal, 2),
            'total_discount' => round($totalDiscount, 2),
            'total_final' => round($totalFinal, 2),
            'applied_promotions' => $appliedPromotions,
            'savings_percentage' => $totalOriginal > 0 ? round(($totalDiscount / $totalOriginal) * 100, 1) : 0
        ];
    }

    /**
     * Vérifie si le panier contient des produits avec promotions
     */
    public function hasPromotions(array $cartItems): bool
    {
        foreach ($cartItems as $item) {
            if ($item['product']->hasActivePromotion()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Formate les informations de promotion pour l'affichage
     */
    public function formatPromotionInfo(array $cartCalculation): array
    {
        $formattedPromotions = [];
        
        foreach ($cartCalculation['applied_promotions'] as $promotionId => $promotionData) {
            $promotion = $promotionData['promotion'];
            $formattedPromotions[] = [
                'name' => $promotion->getName(),
                'code' => $promotion->getCode(),
                'discount' => $promotionData['total_discount'],
                'formatted_discount' => $promotion->getFormattedValue(),
                'product_count' => count($promotionData['products']),
                'products' => array_map(function($item) {
                    return [
                        'name' => $item['product']->getName(),
                        'quantity' => $item['quantity'],
                        'discount' => $item['discount']
                    ];
                }, $promotionData['products'])
            ];
        }
        
        return $formattedPromotions;
    }
}
