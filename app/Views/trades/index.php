<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>株価取得アプリ</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/trade.css">
</head>

<body>
    <header>
        <div></div>
        <div></div>
        <?php if ($user):  ?>
            <div class="header-content"><?= $user['name']?></div>
            <div class="header-content"><?= $isAdmin? "【管理者】":"" ?></div>
            <form class="login-button" action="/logout" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <button type="submit">ログアウト</button>
            </form>
            <span></span>
        <?php else: ?>
            <div class="header-content">ログインしていません</div>
            <form class="login-button" action="/show_login" method="get">
                <button type="submit">ログイン</button>
            </form>
        <?php endif; ?>
    </header>
    
    <?php if (!empty($_SESSION['flash'])): ?>
        <p style="color: green;">
            <?= htmlspecialchars($_SESSION['flash']) ?>
        </p>
    <?php endif; ?>

    

    <div class="list">
        <?php foreach ($trades as $trade): ?>
            <p><?= $trade['id'] ?></p>
            <p><?= $trade['user_id'] ?></p>
            <p><?= $trade['stock_id'] ?></p>
            <p><?= $trade['date'] ?></p>
            <p><?= $trade['price'] ?></p>
            <p><?= $trade['quantity'] ?></p>
            <p><?= $trade['type'] ?></p>
            <p><?= $trade['content'] ?></p>
            <br>
        <?php endforeach; ?>
    </div>

    <?php
        unset($_SESSION['flash'], $_SESSION['errors'], $_SESSION['old']);
    ?>

</body>

<script src="<?= BASE_PATH ?>/js/app.js"></script>

<script>
    const user = <?= json_encode($user) ?>;
    const isAdmin = <?= json_encode($isAdmin) ?>;
</script>
</html>
