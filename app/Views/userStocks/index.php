<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>株価取得アプリ</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/header.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/user-stock-index.css">
</head>

<body>

    <?php
        $backUrl = BASE_PATH. '/';
        $pageTitle = "マイ銘柄編集";
        require __DIR__ . '/../common/header.php';
    ?>

    <!-- メニュー用のボタン類(非表示) -->
    <div class="hidden">
        <form id="logout" action="<?= BASE_PATH ?>/logout" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        </form>
    </div>

    <!-- フラッシュメッセージ -->
    <?php if (!empty($_SESSION['flash'])): ?>
        <p style="color: green;">
            <?= htmlspecialchars($_SESSION['flash']) ?>
        </p>
    <?php endif; ?>

    <?php if (!empty($_SESSION['errors'])): ?>
        <p style="color: green;">
            <?= htmlspecialchars($_SESSION['errors']) ?>
        </p>
    <?php endif; ?>

    <main>
        <section id="searched">
            <div class="section-header">
                <h3 class="section-title">候補リスト</h3>
                <button class="section-header-button" id="search-submit-button">検索</button>
            </div>
            
            <div class="search-container">
                <div class="search-input-block">
                    <input class="search-input" id="search-input" type="text" name="keywords" placeholder="キーワードを入力（スペース区切り）">
                </div>   
            </div>

            <hr>

            <div class="list" id="searched-stock-list"></div>
        </section>

        <section id="users">
            <div class="section-header">
                <h3 class="section-title">マイ銘柄</h3>
                <button class="section-header-button" id="update-button">登録</button>
            </div>

            <div class="operation-button-container">
                <button class="operation-button" id="up-button">上へ</button>
                <button class="operation-button" id="down-button">下へ</button>
                <button class="operation-button" id="select-reset-button">選択解除</button>
            </div>

            <hr>

            <div class="list" id="users-stock-list"></div>
        </section>
    </main>

    <footer>
        <div class="view-switch">
            <button id="view-switch-searched" class="view-switch-button">候補リスト</button>
            <button id="view-switch-users" class="view-switch-button">マイ銘柄</button>
        </div>
    </footer>


    <?php
        unset($_SESSION['flash'], $_SESSION['errors'], $_SESSION['old']);
    ?>

    <script src="<?= BASE_PATH ?>/js/app.js"></script>
    <script src="<?= BASE_PATH ?>/js/utils/menu-item.js"></script>
    <script src="<?= BASE_PATH ?>/js/utils/menu.js"></script>
    <script src="<?= BASE_PATH ?>/js/utils/stocks-view.js"></script>
    <script src="<?= BASE_PATH ?>/js/pages/user-stocks/init.js"></script>

    <script>
        const user = <?= json_encode($user, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const isAdmin = <?= json_encode($isAdmin, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        init();

    </script>
</body>


</html>
