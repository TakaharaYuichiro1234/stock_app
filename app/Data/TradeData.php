<?php
namespace App\Data;

class TradeData {
    public function __construct(
        public int $stock_id,
        public ?string $date,
        public float $price,
        public int $quantity,
        public int $type,
        public string $content
    ) {}
}