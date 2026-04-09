<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, user-scalable=yes">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
    <title>株価取得アプリ</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/header.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/index.css">

    <style>
        .data-paste-area {
            width: 100%;
            height: 200px;
            border: 1px solid white;
            overflow: scroll;
            font-size: 0.6rem;
        }

        th, td {border: 1px solid gray;}
        td {
            max-height: 30px;
            overflow: hidden;
        }

        .btn {
            width: 200px;
        }
    </style>

</head>

<body>
    <!-- ヘッダー -->
    <?php
    $backUrl = $redirect ?? BASE_PATH . '/';
    $pageTitle = "取引データ登録";
    require __DIR__ . '/common/header.php';
    ?>

    <!-- フラッシュメッセージ -->
    <?php
    require __DIR__ . '/common/flash.php';
    ?>

    
    <p>追加するデータを表示する欄を追加。そこで、データチェックと削除もできる</p>
    <p>エクセルからのデータ入力はモーダル画面に移す</p>
    <p>手作業で一つ一つ入力するためのモーダル画面も追加</p>
    <p>すでに登録されているデータを削除したり、編集したりする機能を追加</p>


    <h1>取引データ登録</h1>
    <button class="btn" id="paste-from-clipboard">クリップボードからペースト</button>
    <div class="data-paste-area" id="data-paste-area">
        <table id="data-paste-table"></table>
    </div>

    <button class="btn" id="check-data-button">データをチェック</button>
    <div class="data-paste-area" id="check-result-area">
    </div>

    <button class="btn" id="store-button">データベースに登録</button>
    <div id="unregistered-symbols">
    </div>

    <h1>登録済み取引データ</h1>
    <div>
        <table id="data-table">
            <tr>
                <th>日付</th>
                <th>取引種別</th>
                <th>証券コード</th>
                <th>銘柄名</th>
                <th>口座</th>
                <th>株数</th>
                <th>株価</th>
            </tr>
            
        </table>
    </div>


    <?php
    unset($_SESSION['flash'], $_SESSION['errors'], $_SESSION['old']);
    ?>

</body>

<script type="module" src="<?= BASE_PATH ?>/js/pages/trade/init.js"></script>

<script>
    const user = <?= json_encode($_SESSION['user'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>

</html>