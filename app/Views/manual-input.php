<?php
/** @var array $stock */
/** @var array $stockPrices */
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
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/manual-input.css">
</head>

<body>
    <!-- ヘッダー -->
    <?php
    $backUrl = $redirect ?? BASE_PATH . '/';
    $pageTitle = "株価手動入力";
    require __DIR__ . '/common/header.php';
    ?>

    <!-- フラッシュメッセージ -->
    <?php
    require __DIR__ . '/common/flash.php';
    ?>

    <h1><?= htmlspecialchars($stock['symbol'] . " " . $stock['name']) ?></h1>

    <h2>1. 株価データ入力</h1>

    <div class="input-area" id="manual-input-area">
        <h3>個別入力の場合はこちらから</h3>
        <table>
            <tr>
                <th>日付</th>
                <td class="symbol-input-block">
                    <input
                        type="date"
                        name="date"
                        id="input-date"
                        placeholder="yyyy-mm-dd">
                </td>
            </tr>
            <tr>
                <th>始値</th>
                <td class="symbol-input-block">
                <input
                    type="text"
                    name="price"
                    id="input-open"
                    placeholder="0">
                </td>
            </tr>
            <tr>
                <th>高値</th>
                <td class="symbol-input-block">
                <input
                    type="text"
                    name="price"
                    id="input-hign"
                    placeholder="0">
                </td>
            </tr>
            <tr>
                <th>安値</th>
                <td class="symbol-input-block">
                    <input
                        type="text"
                        name="quantity"
                        id="input-low"
                        placeholder="0">
                </td>
            </tr>
            <tr>
                <th>終値</th>
                <td class="symbol-input-block">
                    <input
                        type="text"
                        name="subtotal"
                        id="input-close"
                        placeholder="0">
                </td>
            </tr>
            <tr>
                <th>出来高</th>
                <td class="symbol-input-block">
                <input
                    type="text"
                    name="price"
                    id="input-volume"
                    placeholder="0">
                </td>
            </tr>
        </table>

        <div>
            <button type="button" class="btn" id="temporary-save">追加</button>
        </div>
    </div>

    <div class="input-area">
        <h3>一括入力の場合はこちらから</h3>
        <button class="btn" id="batch-input-button">一括入力</button>
    </div>

    <h2>2. 入力データの確認</h1>
    <div class="data-view-container" id="temporary-data-view-area">
        <table id="temporary-data-table"></table>
    </div>
    <div id="unregistered-symbols"></div>

    <h2>3. 登録</h1>
    <div>
        <button class="primary-btn" id="store-button">データベースに登録</button>
    </div>
    
    <hr>
    <h2>登録済み株価データ</h2>
    <div>
        <table id="data-table">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>始値</th>
                    <th>高値</th>
                    <th>安値</th>
                    <th>終値</th>
                    <th>出来高</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stockPrices as $price): ?>
                    <tr>
                        <td><?= $price['date'] ?></td>
                        <td><?= $price['open'] ?></td>
                        <td><?= $price['high'] ?></td>
                        <td><?= $price['low'] ?></td>
                        <td><?= $price['close'] ?></td>
                        <td><?= $price['volume'] ?></td>
                    </tr> 
                <?php endforeach ?>
            </tbody>
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

<script type="module" src="<?= BASE_PATH ?>/js/pages/manual-input/init.js"></script>

<script>
    const isAdmin = <?= json_encode($_SESSION['isAdmin'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const user = <?= json_encode($_SESSION['user'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const stock = <?= json_encode($stock, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const stockPrices = <?= json_encode($stockPrices, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    console.log('stock: ', stock);
    console.log('stockPrices: ', stockPrices);

</script>

</html>