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
        $backUrl = BASE_PATH. '/';
        $pageTitle = "管理画面";
        require __DIR__ . '/../common/header.php';
    ?>
    
    <!-- フラッシュメッセージ -->
    <?php if (!empty($_SESSION['flash'])): ?>
        <p style="color: green;">
            <?= htmlspecialchars($_SESSION['flash']) ?>
        </p>
    <?php endif; ?>
    <?php if (!empty($_SESSION['errors']['name'])): ?>
        <p style="color:red;">
            <?= htmlspecialchars($_SESSION['errors']['name']) ?>
        </p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p style="color:red;">エラー：<?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <!-- Javascriptからpostするためのform(非表示) -->
    <div class="hidden">
        <form id="logout" action="<?= BASE_PATH ?>/logout" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        </form>

        <form id="update-stock-prices" action="<?= BASE_PATH ?>/admins/update_stock_prices" method="post" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        </form>

        <form id="post-form" method="post" >
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        </form>

        <form 
            id="update-stock-price" 
            action="<?= BASE_PATH. '/stocks/update_stock_prices/'. htmlspecialchars($stock['id']) ?>" 
            method="post" >
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        </form>

        <form 
            id="delete-stock" 
            action="<?= BASE_PATH. '/stocks/delete/'. htmlspecialchars($stock['id']) ?>" 
            method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        </form>
    </div>

    <section>
        <div class="section-header">
            <h3 class="section-title">新たに登録する銘柄</h3>
        </div>

        <form method="get" class="search-container" id="search-new-form">
            <div class="search-input-block">
                <input class="search-input" type="text" name="symbol" value="<?= htmlspecialchars($symbol) ?>" placeholder="証券コード(例: 7203.T)">
            </div>
            <button class="search-submit" type="submit">検索</button>
        </form>

        <div class="section-content">
            <div class="content-container hidden">
                <form id="stockForm" action="<?= BASE_PATH ?>/api/stocks/store" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="symbol" id="symbol" value="<?= htmlspecialchars($data["symbol"] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="short_name" id="short_name" value="<?= htmlspecialchars($data["shortName"] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="long_name" id="long_name" value="<?= htmlspecialchars($data["longName"] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    
                    <table class="stock-table">
                        <tbody>
                            <tr>
                                <th colspan="2">証券コード</th>
                                <td><span id="result-symbol"></td>
                            </tr>
                            <tr>
                                <th colspan="2">銘柄名</th>
                                <td><input type="text" name="name" id="name" placeholder="登録用の銘柄名を入力"></td>
                            </tr>
                            <tr>
                                <th colspan="2">株価の小数点以下桁数</th>
                                <td><input type="text" name="digit" id="digit" placeholder="0"></td>
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


                    <button type="submit" id="formSubmit">この銘柄を登録</button>
                </form>
                
            </div>

            <div class="section-content-message" id="message-container">
                <p id="message">検索結果がありません。</p>
            </div>
        </div>
    </section>

    <section>
        <div class="section-header">
            <h3 class="section-title">登録済みの銘柄</h3>
        </div>

        <form method="get" class="search-container" id="search-registered-form">
            <div class="search-input-block">
                <input class="search-input" type="text" name="keyword" placeholder="証券コードまたは銘柄名">
            </div>
            <button class="search-submit" type="submit">検索</button>
        </form>

        <div class="list" id="searched-stock-list"></div>
    </section>

    <div class="modal hidden">
        <div class="modal-backdrop"></div>
        <div class="modal-content">
            <button class="modal-close" aria-label="閉じる"></button>
            <div class="modal-content-inner">

                <form id="modal-form" action="<?= BASE_PATH ?>/stocks/update/<?= htmlspecialchars($stock['id']) ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">

                    <input type="hidden" name="symbol" value="<?= htmlspecialchars($stock['symbol'] ?? '') ?>">

                    <div class="modal-content-data-block">
                        <div>銘柄名</div>
                        <div>
                            <input 
                                type="text" 
                                id="input-stock-name" 
                                name="name" 
                                placeholder="銘柄名を入力"
                            >
                        </div>
                    </div>
                    <div class="modal-content-data-block">
                        <div>株価の小数点以下桁数</div>
                        <div>
                            <input 
                                type="text" 
                                id="input-digit" 
                                name="digit" 
                                placeholder="整数(0, 1, 2, ・・・)"
                            >
                        </div>
                    </div>
                    

                    <div>
                        <button type="submit" id="modal-submit">更新</button>
                    </div>
                </form>
            </div>
        </div>
    </div>




    <?php
        unset($_SESSION['flash'], $_SESSION['errors'], $_SESSION['old']);
    ?>

    <script src="<?= BASE_PATH ?>/js/app.js"></script>
    <script src="<?= BASE_PATH ?>/js/utils/menu-item.js"></script>
    <script src="<?= BASE_PATH ?>/js/utils/menu.js"></script>
    <script src="<?= BASE_PATH ?>/js/utils/stocks-view.js"></script>
    <script src="<?= BASE_PATH ?>/js/pages/admins/init.js"></script>

    <script>
        const isAdmin = <?= json_encode($isAdmin) ?>;
        init();


            // document.getElementById('modal-form').addEventListener('submit', (event) => {
            //     event.preventDefault();
            //     // const actionUrl = `${BASE_PATH}/trades/store`;

            //     // const form = document.getElementById('modal-form');
            //     // form.action = actionUrl;
            //     this.submit();
            // });

        document.getElementById('modal-form').addEventListener('submit', async (e) => {
            e.preventDefault(); 

            const form = e.target;
            const url  = form.action;
            const formData = new FormData(form);

            // バリデーションチェック
            const name = formData.get('name');
            const digit = formData.get('digit');
            const validationErrors = [];

            if (name === "") validationErrors.push("名前を入力して下さい");
            if (name.length > 255) validationErrors.push("名前は255文字以下で入力して下さい");
            if (!(/^\d+$/.test(digit))) validationErrors.push("桁数は正の整数を入力してください");

            if (validationErrors.length > 0) {
                showMessages(validationErrors.map(err => ({'message': err, 'type':'error'})));
                return;
            } else {
                showMessages([]);
            }

            // // 新規銘柄登録処理
            // try {
            //     const res = await fetch(url, {
            //         method: 'POST',
            //         body: formData,
            //         credentials: 'same-origin', // セッション / CSRF用
            //     });

            //     if (!res.ok) {
            //         throw new Error('通信エラー');
            //     }

            //     const result = await res.json();

            //     await refreshSearchedStocks("");
            //     alert('登録しました');

            // } catch (err) {
            //     console.error(err);
            //     alert('登録に失敗しました');
            // }
        });
        
    </script>
</body>

