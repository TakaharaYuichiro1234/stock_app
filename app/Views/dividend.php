<?php
/** @var array $stocks */
/** @var array $userStocks */
/** @var array $accounts */
/** @var array $dividends */
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
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/dividend.css">

    <style>
        button:disabled {
            background-color: #333;
            color: #666;
            cursor: not-allowed;
        }
    </style>

</head>

<body>
    <!-- ヘッダー -->
    <?php
    $backUrl = $redirect ?? BASE_PATH . '/';
    $pageTitle = "予想配当データ登録";
    require __DIR__ . '/common/header.php';
    ?>

    <!-- フラッシュメッセージ -->
    <?php
    require __DIR__ . '/common/flash.php';
    ?>

    <h1>配当データ入力</h1>


    <div class="input-area">
        <h3>個別入力の場合はこちらから</h3>

        <div>銘柄を選択</div>
        <input id="input-symbol" value="" list="symbol-select" name="input-symbol" />
        <datalist id="symbol-select">
            <option value=""></option>
        </datalist>

        <h3 id="matched-name"></h3>
        
        <div class="edit-area">
            <table id="dividendTable">
                <tr class="sticky">
                    <th>権利確定日</th>
                    <th>status</th>
                    <th>dps</th>
                    <th>支払(予想)日</th>
                </tr>
                <tbody id="tableBody"></tbody>
            </table>
            <p id="message"></p>
            <button id="update-button">更新</button>
            <button id="add-row-button">空の行を追加</button>
            <button id="add-predict-button">予測を追加</button>
        </div>


        <button id="get-dividend-button">Yfinanceから取得</button>
    </div>

    <div class="input-area">
        <h3>一括入力の場合はこちらから</h3>
        <button class="btn" id="batch-input-button">一括入力</button>

        <h3>入力データの確認</h1>
        <div class="data-view-container" id="temporary-data-view-area">
            <table id="temporary-data-table"></table>
        </div>

        <h3>登録</h3>
        <div>
            <button class="primary-btn" id="store-button">データベースに登録</button>
        </div>
    </div>

    <div>
        <h1>すべての配当データ</h1>
        <table id="all-data-table">
            <tr>
                <th>銘柄</th>
                <th>証券コード</th>
                <th>権利確定日</th>
                <th>status</th>
                <th>dps</th>
                <th>支払(予想)日</th>
            </tr>
            <tbody id="all-data-table-body"></tbody>
        </table>    
    </div>



    <div class="modal hidden">
        <div class="modal-backdrop"></div>
        <div class="modal-content">
            <button class="modal-close" aria-label="閉じる"></button>
            <div class="modal-content-inner">
                <div class="paste-button-container">
                    <button class="paste-button" id="paste-from-clipboard">クリップボードからペースト</button>
                    <button class="paste-button" id="paste-from-csv">CSVから追加</button>
                </div>

                <div class="data-view-container" id="data-paste-area">
                    <table id="data-paste-table"></table>
                </div>
           
                <button class="btn" id="add-to-temporary-data">追加</button>

            </div>

        </div>
    </div>


    <?php
    unset($_SESSION['flash'], $_SESSION['errors'], $_SESSION['old']);
    ?>

</body>

<script type="module" src="<?= BASE_PATH ?>/js/pages/dividend/init.js"></script>

<script>
    const user = <?= json_encode($_SESSION['user'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const stocks = <?= json_encode($stocks, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const accounts = <?= json_encode($accounts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const userStocks = <?= json_encode($userStocks, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const dividends = <?= json_encode($dividends, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;


</script>

</html>