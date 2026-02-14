<?php
namespace App\Services;
use PDO;
use App\Models\Stock;

class StockService {
    private PDO $pdo;
    private Stock $stockModel;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->stockModel = new Stock($this->pdo);
    }

    public function initCreate(?string $symbol): array {
        $data = null;
        $error = null;

        if ($symbol !== null && $symbol !== '') {
            $url = "http://127.0.0.1:5000/stock?symbol=" . urlencode($symbol);

            $context = stream_context_create([
                'http' => [
                    'timeout' => 3
                ]
            ]);

            $json = @file_get_contents($url, false, $context);

            if ($json === false) {
                $error = "Python APIに接続できません";
            } else {
                $data = json_decode($json, true);
                if (isset($data["error"])) {
                    $error = $data["error"];
                }
            }
        }

        return [$error, $data];
    }

    public function isSymbolRegistered(?string $symbol): bool {
        return $symbol ? $this->stockModel->existsBySymbol($symbol) : false;
    }
}