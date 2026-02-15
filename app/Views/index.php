<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, user-scalable=yes">
    <title>株価取得アプリ</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/header.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/index.css">
</head>

<body>
    <!-- ヘッダー -->
    <?php
        $backUrl = null;
        $pageTitle = $user ? "お気に入り銘柄一覧" : "登録銘柄一覧";
        require __DIR__ . '/common/header.php';
    ?>

    <!-- フラッシュメッセージ -->
    <?php
        require __DIR__ . '/common/flash.php';
    ?>

    <!-- 株価表示用の関数 -->
    <?php
        require_once __DIR__ . '/../Helpers/ViewHelper.php';
    ?>

    <div>
        <?= count($stocks) === 0 ? "対象の銘柄がありません。": "" ?> 
    </div>

    <div class="list">
        <?php foreach ($stocks as $stock): ?>
            <div class="stock-board" id="list-content_<?= htmlspecialchars($stock['id']) ?>">
                <div class="stock-board-name-block">
                    <div class="stock-board-name"><?= htmlspecialchars($stock['name']) ?></div>    
                    <div class="stock-board-info-block">
                        <div class="stock-board-symbol"><?= htmlspecialchars($stock['symbol']) ?></div>
                        <div class="stock-board-latest-date"><?= htmlspecialchars($stock['latest_date']) ?></div>
                    </div>         
                </div>

                <div class="stock-board-price">
                    <div class="stock-board-price-int-part">
                        <?= $stock['latest_close'] ? number_format(floor($stock['latest_close'])) : '-' ?>
                    </div>

                    <div class="stock-board-price-decimal-part">
                        <?= $stock['latest_close'] ? ViewHelper::formatDecimalPart($stock['latest_close'], $stock['digit']) : '' ?>
                    </div>
                </div>

                <div class="stock-board-diff-block">
                    <div class="stock-board-diff <?= ViewHelper::diffClass($stock['diff']) ?>"> <?= ViewHelper::formatDiff($stock['diff'], $stock['digit']) ?></div>
                    <div class="stock-board-percent-diff <?= ViewHelper::diffClass($stock['diff']) ?>"> <?= ViewHelper::formatDiff($stock['percent_diff'],2) ?>%</div>
                </div>
            </div>

        <?php endforeach; ?>
    </div>

    <?php
        unset($_SESSION['flash'], $_SESSION['errors'], $_SESSION['old']);
    ?>

</body>

<script src="<?= BASE_PATH ?>/js/app.js"></script>
<script src="<?= BASE_PATH ?>/js/utils/menu-item.js"></script>
<script src="<?= BASE_PATH ?>/js/utils/menu.js"></script>
<script src="<?= BASE_PATH ?>/js/pages/index/init.js"></script>

<script>
    const user = <?= json_encode($user, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const isAdmin = <?= json_encode($isAdmin, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const stocks = <?= json_encode($stocks, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    init();
</script>
</html>
