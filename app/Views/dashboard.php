<?php
/** @var array $user */
/** @var array $stocks */
/** @var array $expectedDividends */
/** @var bool $isAdmin */

// 株価表示用の関数
require_once __DIR__ . '/../Helpers/ViewHelper.php';
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
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/dashboard.css">
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

    <div class="filterBox" id="filterBox" style="display:none; border:1px solid #ccc; padding:10px; width:200px;">
        <div id="filterList"></div>
    </div>

    <div class="filterBox" id="accountFilterBox" style="display:none; border:1px solid #ccc; padding:10px; width:200px;">
        <div id="accountFilterList"></div>
    </div>

    <section id="various-panels"></section>
    
    <section id="overall-panels">
        <div class="parts-container" id="parts_ovarall">
            <div class="container-header">
                <h2>銘柄別詳細</h2>
            </div>
            <div id="overall-container"></div>
        </div>
    </section>
</body>



<!-- <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script> -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/luxon@3.5.0/build/global/luxon.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.3.1/dist/chartjs-adapter-luxon.umd.min.js"></script> 
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script type="module" src="<?= BASE_PATH ?>/js/pages/dashboard/init.js"></script>

<script>
    Chart.register(ChartDataLabels);
    const user = <?= json_encode($user, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const isAdmin = <?= json_encode($isAdmin, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const stocks = <?= json_encode($stocks, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const expected_dividends = <?= json_encode($expectedDividends, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>

</html>