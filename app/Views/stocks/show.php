<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>株価取得アプリ</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/header.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/show.css">
</head>

<body>
    <!-- ヘッダー -->
    <?php
        $backUrl = $redirect ?? BASE_PATH. '/';
        $pageTitle = "詳細";
        require __DIR__ . '/../common/header.php';
    ?>

    <!-- フラッシュメッセージ -->
    <?php if (!empty($_SESSION['flash'])): ?>
        <p style="color: green;">
            <?= htmlspecialchars($_SESSION['flash']) ?>
        </p>
    <?php endif; ?>
    <?php if (!empty($_SESSION['errors'])): ?>
        <p style="color:red;">
            エラー
        </p>
    <?php endif; ?>

    <!-- メニュー用のフォーム(非表示) -->
    <div class="hidden">
        <form id="logout" action="<?= BASE_PATH ?>/logout" method="post">
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

    <?php
        function formatDiff($value, $digit) {
            return ($value > 0 ? "+": "") . strval(number_format($value, $digit));
        }

        function formatDecimalPart($value, $digit) {
            if ($digit == 0) return "";

            $decimalPart = $value - floor($value);
            $multi = pow(10, $digit);
            return '.' . strval(floor($decimalPart*$multi));
        }
    ?>

    <section>
        <div class="stock-board">
            <div class="stock-board-name-block">
                <div class="stock-board-name"><?= htmlspecialchars($stock['name']) ?></div>    
                <div class="stock-board-info-block">
                    <div class="stock-board-symbol"><?= htmlspecialchars($stock['symbol']) ?></div>
                    <div class="stock-board-latest-date"><?= htmlspecialchars($latest['date']) ?></div>
                </div>         
            </div>

            <div class="stock-board-price">
                <div class="stock-board-price">
                    <div class="stock-board-price-int-part">
                        <?= $latest['close'] ? number_format(floor($latest['close'])) : '-' ?>
                    </div>

                    <div class="stock-board-price-decimal-part">
                        <?= $latest['close'] ? formatDecimalPart($latest['close'], $stock['digit']) : '' ?>
                    </div>
                </div>
            </div>

            <?php
                function diffClass($d) {
                    if ($d > 0) {
                        return 'diff-plus';
                    } elseif ($d < 0) {
                        return 'diff-minus';
                    } else {
                        return 'diff-zero';
                    }
                }
            ?>

            <div class="stock-board-diff-block">
                <div class="stock-board-diff <?= diffClass($diff) ?>"> <?= formatDiff($diff, $stock['digit']) ?></div>
                <div class="stock-board-percent-diff <?= diffClass($diff) ?>"> <?= formatDiff($percent_diff, 2) ?>%</div>
            </div>
        </div>
    </section>

    <section class="user-valid">
        <div class="amount-section">
            <?php if ($tradeAmounts): ?>
                <div class="amount-block">
                    <div class="amount-caption">保有数量</div>
                    <div class="amount-value"><?= number_format($tradeAmounts['quantity']) ?></div>
                </div>
                <div class="amount-block">
                    <div class="amount-caption">評価額</div>
                    <div class="amount-value"><?= number_format($tradeAmounts['quantity'] * $latest['close'], $stock['digit']) ?></div>
                </div>
                <div class="amount-block">
                    <div class="amount-caption">平均取得単価</div>
                    <div class="amount-value"> 
                        <?= formatDiff(
                            $tradeAmounts['quantity'] > 0 ? 
                            $tradeAmounts['total'] / $tradeAmounts['quantity'] : 
                            0, 
                            $stock['digit']
                        ) ?>
                    </div>
                </div>
                <div class="amount-block">
                    <?php
                        $profit = $tradeAmounts['quantity'] > 0 ?
                                  $tradeAmounts['quantity'] * $latest['close'] - $tradeAmounts['total'] :
                                  0;
                    ?>
                    <div class="amount-caption">評価損益</div>
                    <div class="amount-value <?= diffClass($profit) ?>"> 
                        <?= formatDiff($profit, $stock['digit']) ?>
                    </div>
                </div>

            <?php endif ?>
        </div>
    </section>
    
    <section>
        <form class="select-chart" id="select-chart">
            <input id="item-1" class="radio-inline__input" type="radio" name="accessibleradio" value="daily" checked="checked"/>
            <label class="radio-inline__label" for="item-1">
                日足
            </label>
            <input id="item-2" class="radio-inline__input" type="radio" name="accessibleradio" value="weekly"/>
            <label class="radio-inline__label" for="item-2">
                週足
            </label>
            <input id="item-3" class="radio-inline__input" type="radio" name="accessibleradio" value="monthly"/>
            <label class="radio-inline__label" for="item-3">
                月足
            </label>
        </form>

        <div class="chart-container">
            <div id="chart"></div>
        </div>

        <div class="clicked-data-table-container">
            <table class="clicked-data-table">
                <thead>
                    <tr>
                        <th scope="col">日付</th>
                        <th scope="col">始値</th>
                        <th scope="col">高値</th>
                        <th scope="col">安値</th>
                        <th scope="col">終値</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th scope="row" id="clicked-date">-</th>
                        <td id="clicked-open">-</td>
                        <td id="clicked-high">-</td>
                        <td id="clicked-low">-</td>
                        <td id="clicked-close">-</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
    
    <section class="user-valid">
        <button id="show-modal-button">取引情報入力</button>
    </section>

    <section class="user-valid">
        <h3 class="section-title">取引履歴</h3>
        <div class="trade-list">
            <?php if ($trades): ?>
                <?php foreach ($trades as $trade): ?>
                    <div class="trade-content">
                        <div class="trade-content-left">
                            <?php if($trade['type'] === 1): ?>
                                <div class="trade-type buy-color">買</div>
                            <?php elseif($trade['type'] === 2): ?>
                                <div class="trade-type sell-color">売</div>
                            <?php else: ?>
                                <div class="trade-type">他</div>
                            <?php endif ?>
                        </div>
                        <div class="trade-content-right">
                            <div class="trade-date"><?= $trade['date'] ?></div>
                            <div>単価：<?= $trade['price'] ?></div>
                            <div>数量：<?= $trade['quantity'] ?></div>
                            <div><?= $trade['content'] ?></div>
                            <class class="trade-content-button-cotainer">
                                <button onclick="">編集</button>
                                <button>削除</button>
                            </class>
                        </div>
                    </div>
                <?php endforeach ?>
            <?php else: ?>
                データがありません。
            <?php endif ?>
        </div>
    </section>

    <div class="modal hidden">
        <div class="modal-backdrop"></div>
        <div class="modal-content">
            <button class="modal-close" aria-label="閉じる"></button>
            <div class="modal-content-inner">

                <div class="clicked-data-table-container">
                    <div id="selected-date-message"></div>
                    <table class="clicked-data-table">
                        <thead>
                            <tr>
                                <th scope="col">始値</th>
                                <th scope="col">高値</th>
                                <th scope="col">安値</th>
                                <th scope="col">終値</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td id="modal-open">-</td>
                                <td id="modal-high">-</td>
                                <td id="modal-low">-</td>
                                <td id="modal-close">-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
               
                <form id="modal-form"  method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                    <input type="hidden" name="uuid" value="<?= htmlspecialchars($_SESSION['user']['uuid']) ?>">
                    <input type="hidden" name="stock_id" value="<?= htmlspecialchars($stock['id']) ?>">
                    <div class="modal-content-data-block">
                        <div>日付</div>
                        <div>
                            <input 
                                type="date" 
                                name="date"
                                id="input-date" 
                                placeholder="yyyy-mm-dd">
                        </div>
                    </div>
                    <div class="modal-content-data-block">
                        <div>単価</div>
                        <div>
                            <input 
                                type="text"
                                name="price"
                                id="input-price" 
                                placeholder="0">
                        </div>
                    </div>
                    <div class="modal-content-data-block">
                        <div>数量</div>
                        <div>
                            <input 
                                type="text"
                                name="quantity"
                                id="input-quantity"
                                placeholder="0">
                        </div>
                    </div>
                    <div class="modal-content-data-block">
                        <div>種類</div>
                        <div>
                            <select name="type" name="type" id="modal-input-type">
                                <option value="1">買付</option>
                                <option value="2">売付</option>
                                <option value="0">メモ</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-content-data-block">
                        <div>詳細</div>
                        <div>
                            <textarea
                                name="content"
                                id="input-content"
                                placeholder="アクションの詳細"
                            >
                            </textarea>
                        </div>
                    </div>

                    <div>
                        <button type="submit" id="modal-submit">登録</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/lightweight-charts/dist/lightweight-charts.standalone.production.js"></script>

    <script src="<?= BASE_PATH ?>/js/app.js"></script>
    <script src="<?= BASE_PATH ?>/js/utils/menu-item.js"></script>
    <script src="<?= BASE_PATH ?>/js/utils/menu.js"></script>
    <script src="<?= BASE_PATH ?>/js/pages/stocks/chart-mojule.js"></script>
    <script src="<?= BASE_PATH ?>/js/pages/stocks/show.js"></script>
    
    <script >
        (async () => {
            const user = <?= json_encode($user, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const isAdmin = <?= json_encode($isAdmin, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const stockId = <?= json_encode($stock['id'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;

            initShow(user, isAdmin, stockId);
        })();        
    </script>


    <?php
        unset($_SESSION['flash'], $_SESSION['errors'], $_SESSION['old']);
    ?>
</body>

</html>
