<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, user-scalable=yes">
    <title>銘柄情報編集</title>
</head>
<body>

    <h1>銘柄情報編集</h1>

    <form action="<?= BASE_PATH ?>/stocks/update/<?= htmlspecialchars($stock['id']) ?>" method="post">

        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="symbol" value="<?= htmlspecialchars($stock['symbol'] ?? '') ?>">

        <label>
            銘柄名：
             <input type="text" name="name" value="<?= htmlspecialchars($_SESSION['old']['name'] ?? $stock['name']) ?>">
        </label>
        <br>

        <label>
            株価の小数点以下桁数：
            <input type="text" name="digit" value="<?= htmlspecialchars($_SESSION['old']['digit'] ?? $stock['digit']) ?>">
        </label>
        <br>
       
        <button type="submit">更新</button>
    </form>
</body>
</html>

<?php
    unset($_SESSION['flash'], $_SESSION['errors'], $_SESSION['old']);
?>
