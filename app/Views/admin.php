<?php
/** @var bool $isAdmin */
/** @var string $symbol */
/** @var array $stocks */
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, user-scalable=yes">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
    <title>株価取得アプリ</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/header.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/admin.css">
</head>

<body>
    <!-- ヘッダー -->
    <?php
    $backUrl = BASE_PATH . '/';
    $pageTitle = "管理画面";
    require __DIR__ . '/common/header.php';
    ?>

    <!-- フラッシュメッセージ -->
    <?php
    require __DIR__ . '/common/flash.php';
    ?>

    <!-- Javascriptからpostするためのform(非表示) -->
    <div class="hidden">
        <form id="update-stock-prices-all" action="<?= BASE_PATH ?>/admins/update_stock_prices_all" method="post" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        </form>
    </div>

    <!-- 登録済みの銘柄を表示するセクション -->
    <section>
        <div class="search-container">
            <div class="search-input-block">
                <input class="search-input" id="input-symbol" value=""  placeholder="証券コードまたは銘柄名"/>
            </div>
            <button id="search-input-clear-btn" class="search-submit" type="button">クリア</button>
        </div>

        <div class="filter-options-container">
            <input type="checkbox" id="view-tentative-only" name="view-tentative-only" value="on">
            <label for="view-tentative-only">仮登録のみ表示</label>            
        </div>

        <div class="list" id="searched-stock-list"></div>
    </section>

    <!-- 銘柄名などを編集するためのモーダル画面 -->
    <div class="modal hidden">
        <div class="modal-backdrop"></div>
        <div class="modal-content">
            <button class="modal-close" aria-label="閉じる"></button>
            <div class="modal-content-inner">

                <form id="modal-form" method="post">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                    <input type="hidden" name="stock_id" id="modal-form-stock-id" >
                    <input type="hidden" name="symbol" id="modal-form-symbol" value="<?= htmlspecialchars($data["symbol"] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="short_name" id="short_name" value="<?= htmlspecialchars($data["shortName"] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="long_name" id="long_name" value="<?= htmlspecialchars($data["longName"] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                    <table class="stock-table">
                        <tbody>
                            <tr>
                                <th colspan="2">証券コード</th>
                                <!-- <td><span id="modal-symbol"></td> -->
                                <td><input type="text" name="symbol" id="input-stock-symbol" disabled></td>
                            </tr>
                            <tr>
                                <th colspan="2">銘柄名</th>
                                <td><input type="text" name="name" id="input-stock-name" placeholder="登録用の銘柄名を入力"></td>
                            </tr>
                            <tr>
                                <th colspan="2">株価の小数点以下桁数</th>
                                <td><input type="text" name="digit" id="input-digit" placeholder="0"></td>
                            </tr>
                            <tr>
                                <th colspan="3"><span id="result-date"></span>の株価データ</th>
                            </tr>
                            <tr>
                                <th></th>
                                <th>始値</th>
                                <td><span id="result-open"></td>
                            </tr>
                            <tr>
                                <th></th>
                                <th>高値</th>
                                <td><span id="result-high"></td>
                            </tr>
                            <tr>
                                <th></th>
                                <th>低値</th>
                                <td><span id="result-low"></td>
                            </tr>
                            <tr>
                                <th></th>
                                <th>終値</th>
                                <td><span id="result-close"></td>
                            </tr>
                            <tr>
                                <th></th>
                                <th>出来高</th>
                                <td><span id="result-volume"></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="section-content-message" id="modal-message-container">
                    </div>

                    <div>
                        <button type="submit" id="modal-submit">更新</button>
                        <button type="button" id="manual-input-btn">株価手動入力</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <?php
    unset($_SESSION['flash'], $_SESSION['errors'], $_SESSION['old']);
    ?>

    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <script type="module" src="<?= BASE_PATH ?>/js/pages/admin/init.js"></script>

    <script>
        const isAdmin = <?= json_encode($isAdmin, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const stocks = <?= json_encode($stocks, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
</body>