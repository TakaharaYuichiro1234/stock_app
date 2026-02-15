<header>
    <?php if ($backUrl): ?>
        <button class="prev-button" onclick="location.href='<?= htmlspecialchars($backUrl) ?>'">
            <span class="lessthan"></span>
        </button>
    <?php else: ?>
        <div></div>
    <?php endif; ?>

    <div class="header-title">
        <p id="header-title-text"><?= htmlspecialchars($pageTitle) ?></p>
    </div>

    <div class="header-user">
        <p><?= $user ? htmlspecialchars($user['name']) : 'ログインしていません' ?></p>
        <p><?= $isAdmin ? "🛡️" : "" ?></p>
    </div>

    <div class="menu-container">
        <button class="three-dot-leader" id="menu-btn">
            <span class="dot"></span>
        </button>

        <div class="menu-panel" id="menu-panel"></div>
    </div>

    <!-- Javascriptからpostするためのform(非表示) -->
    <div class="hidden">
        <form id="logout" action="<?= BASE_PATH ?>/logout" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        </form>
    </div>
</header>
