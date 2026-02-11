<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>株価取得アプリ</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/header.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/index.css">
</head>

<body>

    <?php
        $backUrl = null;
        $pageTitle = $user ? "マイ銘柄一覧" : "登録銘柄一覧";
        require __DIR__ . '/../common/header.php';
    ?>

    <!-- メニュー用のボタン類(非表示) -->
    <div class="hidden">
        <form id="logout" action="<?= BASE_PATH ?>/logout" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        </form>

        <form id="update-stock-prices" action="<?= BASE_PATH ?>/stocks/update_stock_prices" method="post" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        </form>
    </div>

    <!-- フラッシュメッセージ -->
    <?php if (!empty($_SESSION['flash'])): ?>
        <p style="color: green;">
            <?= htmlspecialchars($_SESSION['flash']) ?>
        </p>
    <?php endif; ?>

    <!-- 株価表示用の関数（後で整理） -->
    <?php
        function formatDiff($value, $digit) {
            return ($value > 0 ? "+": "") . strval(number_format($value, $digit));
        }

        function formatDecimalPart($value, $digit) {
            if ($digit == 0) return "";

            $decimalPart = $value - floor($value);
            $multi = pow(10, $digit);
            return '.' . strval(floor($decimalPart*$multi));
        }
    ?>

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
                        <?= $stock['latest_close'] ? formatDecimalPart($stock['latest_close'], $stock['digit']) : '' ?>
                    </div>
                </div>

                <?php
                    if ($stock['diff'] > 0) {
                        $diffClass = 'diff-plus';
                    } elseif ($stock['diff'] < 0) {
                        $diffClass = 'diff-minus';
                    } else {
                        $diffClass = 'diff-zero';
                    }
                ?>

                <div class="stock-board-diff-block">
                    <div class="stock-board-diff <?= $diffClass ?>"> <?= formatDiff($stock['diff'], $stock['digit']) ?></div>
                    <div class="stock-board-percent-diff <?= $diffClass ?>"> <?= formatDiff($stock['percent_diff'],2) ?>%</div>
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

<script>
    const user = <?= json_encode($user, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const isAdmin = <?= json_encode($isAdmin, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    const stocks = <?= json_encode($stocks) ?>;
    for (stock of stocks) {
        const id = stock['id'];
        document.getElementById(`list-content_${id}`).addEventListener('click', () => {
            window.location.href = `${BASE_PATH}/stocks/show/${id}`;
        })
    }

    // メニューアイテム初期設定
    initMenu();

    function initMenu() {
        const items = [];

        if (isAdmin) {
            items.push(
                new MenuItem({
                    caption: '🛡️管理画面',
                    name: 'admin',
                    action: () => location.href = `${BASE_PATH}/admins`
                })
            );
        }

        if (user) {
            items.push(
                new MenuItem({
                    caption: 'マイ銘柄編集',
                    name: 'user-stock',
                    action: () => location.href = `${BASE_PATH}/user-stocks`
                }),
                new MenuItem({
                    caption: 'ログアウト',
                    name: 'logout',
                    action: () => document.getElementById('logout').submit()
                })
            );
        } else {
            items.push(
                new MenuItem({
                    caption: 'ログイン',
                    name: 'login',
                    action: () => location.href = `${BASE_PATH}/show_login`
                })
            );
        }

        const menu = new Menu({
            menuBtnId: 'menu-btn',
            menuPanelId: 'menu-panel',
            items
        });

        menu.init();

    }

</script>
</html>
