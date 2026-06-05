<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, user-scalable=yes">
    <title>株価取得アプリ</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/login.css">
</head>

<body>
    <form class="form-login" method="post" action="<?= BASE_PATH ?>/register">
        <h1>会員登録</h1>
        <div class="input-block">
            <div class="input-block-caption">ユーザーID</div>
            <div class="input-block-input">
                <input type="email" name="email" required>
            </div>
        </div>
        <div class="input-block">
            <div class="input-block-caption">パスワード</div>
            <div class="input-block-input">
                <input type="password" name="password" required>
            </div>
        </div>
        <div class="button-block">
            <button>会員登録</button>
        </div>
    </form>

    <div class="login-register-switching">
        <a href="<?= BASE_PATH ?>/show_login">ログインはこちら</a>
    </div>
  </div>
</body>