<?php if (!empty($_SESSION['flash'])): ?>
    <p style="color: green;">
        <?= htmlspecialchars($_SESSION['flash']) ?>
    </p>
<?php endif; ?>

<?php if ($errors): ?>
    <?php foreach ($errors as $error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endforeach ?>
<?php endif; ?>