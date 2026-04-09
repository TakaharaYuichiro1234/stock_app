<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, user-scalable=yes">
    <title>株価取得アプリ</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/header.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/index.css">


    <style>
        th, td {border: 1px solid gray;}
    </style>
</head>

<body>
    <!-- ヘッダー -->
    <?php
    $backUrl = null;
    $pageTitle = "ダッシュボード";
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

    <h1>ダッシュボード予定地</h1>

    <div id="trade-summary-container"></div>

    <!-- <table>
        <tr>
            <th>stock_id</th>
            <th>account_id</th>
            <th>total quantity</th>
            <th>average price</th>
        </tr>

        <?php foreach($tradeSummary as $trade): ?>
            <tr>
                <td><?= htmlspecialchars($trade['stock_id'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($trade['account_id'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($trade['total_quantity'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($trade['average_price'], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        <?php endforeach ?>
    </table> -->

</body>

<script type="module" src="<?= BASE_PATH ?>/js/pages/dashboard/init.js"></script>

<script>
    const user = <?= json_encode($user, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const isAdmin = <?= json_encode($isAdmin, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const stocks = <?= json_encode($stocks, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const tradeSummary = <?= json_encode($tradeSummary, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const accounts = <?= json_encode($accounts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    
</script>

</html>