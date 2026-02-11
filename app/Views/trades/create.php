<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>株価取得アプリ</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/create.css">
</head>

<body>
    <?php if (!empty($_SESSION['errors']['name'])): ?>
        <p style="color:red;">
            <?= htmlspecialchars($_SESSION['errors']['name']) ?>
        </p>
    <?php endif; ?>

    <form method="get" class="search-container">
        <div class="search-input-block">
            <input class="search-input" type="text" name="symbol" value="<?= htmlspecialchars($symbol) ?>" placeholder="7203.T">
        </div>
        <button class="search-submit" type="submit">検索</button>
    </form>

    <hr>

    <?php if ($error): ?>
        <p style="color:red;">エラー：<?= htmlspecialchars($error) ?></p>
    <?php endif; ?>


    <div class="content-container hidden">
        <h2><?= htmlspecialchars($data["symbol"]) ?> の銘柄情報</h2>
        <ul>
            <li>名称：<?= $data["shortName"] ?></li>
            <li>始値：<?= $data["open"] ?></li>
            <li>高値：<?= $data["high"] ?></li>
            <li>安値：<?= $data["low"] ?></li>
            <li>終値：<?= $data["close"] ?></li>
            <li>出来高：<?= number_format($data["volume"]) ?></li>
        </ul>

        <form action="/stocks/store" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="symbol" value="<?= htmlspecialchars($data["symbol"] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="short_name" value="<?= htmlspecialchars($data["shortName"] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="long_name" value="<?= htmlspecialchars($data["longName"] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            
            <div>
                <label>
                    銘柄名：
                    <input
                        type="text"
                        name="name"
                        id="name"
                        value="<?= htmlspecialchars(
                            $_SESSION['old']['name']
                            ?? ($data['shortName'] ?? ''),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        placeholder="登録用の銘柄名を入力"
                    >
                </label>
            </div>

            <div>
                <label>
                    金額の小数点以下桁数：
                    <input
                        type="text"
                        name="digit"
                        id="digit"
                        value="<?= htmlspecialchars(
                            $_SESSION['old']['digit']
                            ?? ($data['digit'] ?? ''),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        placeholder="0"
                    >
                </label>
            </div>

            <button type="submit" id="formSubmit" <?= $error? 'disabled': ''?>>この銘柄を登録</button>
        </form>
        <p id="message"></p>
    </div>



    <?php
        unset($_SESSION['flash'], $_SESSION['errors'], $_SESSION['old']);
    ?>
    
    <script src="<?= BASE_PATH ?>/js/app.js"></script>

    <script>
        const data = <?= json_encode($data) ?>;
        const error = <?= json_encode($error) ?>;

        const container = document.querySelector(".content-container");
        
        if (!data) {
            container.classList.add("hidden");
        } else {
            if (!data['symbol']) {
                container.classList.add("hidden");
            } else {
                container.classList.remove("hidden");
            }
        }

        //　バリデーションチェック
        document.getElementById('formSubmit').addEventListener('click',function(event){
            const name = document.getElementById('name').value;
            const digit = document.getElementById('digit').value;
            const validationErrors = [];

            if (name === "") validationErrors.push("名前を入力して下さい");
            if (name.length > 255) validationErrors.push("名前は255文字以下で入力して下さい");
            if (!(/^\d+$/.test(digit))) validationErrors.push("桁数は正の整数を入力してください");

            if (validationErrors.length > 0) {
                document.getElementById('message').innerHTML = validationErrors.join("<br>");
                event.preventDefault();
            } else {
                document.getElementById('message').innerHTML = "";
            }
        });
    </script>
</body>

