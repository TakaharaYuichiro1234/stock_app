async function initShow(user, isAdmin, stockId) {
    initMenu(user, isAdmin, stockId);
    initView(user);
 
    // この銘柄の株価データ取得
    let pricesData = null;
    try {
        const res = await fetch(`${BASE_PATH}/api/stocks/get_for_chart/${stockId}`);
        if (!res.ok) throw new Error('APIエラー');
        const json = await res.json();
        pricesData = json.data;
    } catch (err) {
        console.error(err);
        return;
    }

    // このユーザーがこの銘柄に登録した取引データ取得
    let tradeData = [];
    if (user){
        try {
            const res = await fetch(`${BASE_PATH}/api/trades/get_for_chart/${user['uuid']}/${stockId}`);
            if (!res.ok) throw new Error('APIエラー');
            const json = await res.json();
            tradeData = json.data;
        } catch (err) {
            console.error(err);
            return;
        }
    }

    // 株価チャート初期化
    const chart = new ChartModule('chart');
    chart.init();
    chart.drawChart(pricesData['daily'], tradeData['daily']?? []);
    

    // 日足・週足・月足の初期化
    const granularity = localStorage.getItem('stock-app:chart-granularity') || 'daily';
    const selectChart = document.getElementById('select-chart');
    selectChart.accessibleradio.value = granularity;
    const monthRange = (granularity === "monthly") ? 48 : (granularity === "weekly") ? 12 : 3; 
    chart.drawChart(pricesData[granularity], tradeData[granularity]?? [], monthRange);

    // 日足・週足・月足の変更イベント登録
    selectChart.addEventListener('change', () => {
        const granularity = selectChart.accessibleradio.value;
        const monthRange = (granularity === "monthly") ? 48 : (granularity === "weekly") ? 12 : 3; 
        chart.drawChart(pricesData[granularity], tradeData[granularity]?? [], monthRange);
        localStorage.setItem('stock-app:chart-granularity', granularity);
    });

    // チャートをクリックした時のカスタムイベント
    document.addEventListener("click-chart", (e) => {
        const { time } = e.detail;
    
        const chartType = selectChart.accessibleradio.value;
        const prices = pricesData[chartType].find( p => p.time ===  time);

        // チャートクリック時の値を表示する各要素に値を入力
        showChartClickedData(time, prices);

        // モーダル画面の各要素にも入力
        showModalData(time, prices);
    });

    // モーダル画面表示・非表示
    document.getElementById("show-modal-button").addEventListener("click", () => {
        document.querySelector(".modal").classList.remove("hidden");
    });

    document.querySelector(".modal-close").addEventListener("click", () => {
        document.querySelector(".modal").classList.add("hidden");
    });

    // モーダル画面内の日付を変更した時のイベント
    const inputDate = document.getElementById("input-date");
    inputDate.addEventListener("change", () => {
        const date = inputDate.value;
        const chartType = selectChart.accessibleradio.value;
        const prices = pricesData[chartType].find(p => p.time ===  date);
        showModalData(date, prices);
    });

    function showChartClickedData(date, prices) {
        document.getElementById('clicked-date').innerHTML = date;

        const domPrices = [
            document.getElementById('clicked-open'),
            document.getElementById('clicked-high'),
            document.getElementById('clicked-low'),
            document.getElementById('clicked-close')
        ];
        
        if (prices) {
            domPrices[0].innerHTML = prices['open'];
            domPrices[1].innerHTML = prices['high'];
            domPrices[2].innerHTML = prices['low'];
            domPrices[3].innerHTML = prices['close'];
        } else {
            domPrices.forEach(dom => dom.innerHTML = '-');
        }
    }

    function showModalData(date, prices) {
        document.getElementById('input-date').value = date;

        const domMessage = document.getElementById("selected-date-message");
        const domPrices = [
            document.getElementById('modal-open'),
            document.getElementById('modal-high'),
            document.getElementById('modal-low'),
            document.getElementById('modal-close')
        ];
        const domInputPrice = document.getElementById('input-price');

        if (date) {
            domMessage.innerHTML = `${date}の株価`;

            if (prices) {
                domPrices[0].innerHTML = prices['open'];
                domPrices[1].innerHTML = prices['high'];
                domPrices[2].innerHTML = prices['low'];
                domPrices[3].innerHTML = prices['close'];
                domInputPrice.value = prices['close'];
            } else {
                domPrices.forEach(dom => dom.innerHTML = '-');
            }
        } else {
            domMessage.innerHTML = '';
            domPrices.forEach(dom => dom.innerHTML = '-');

        }
    }

    // モーダル画面でsubmitされたとき
    document.getElementById('modal-submit').addEventListener('click', (event) => {
        event.preventDefault();
        const actionUrl = `${BASE_PATH}/trades/store`;

        const form = document.getElementById('modal-form');
        form.action = actionUrl;
        form.submit();
    });

    // 画面サイズを変更した時にチャートのサイズを再調整
    window.addEventListener('resize', () => {
        chart.resizeChart(document.getElementById('chart').clientWidth, document.getElementById('chart').clientHeight);
    });
}

function initMenu(user, isAdmin, stockId) {
    const items = [];

    if (isAdmin) {
        items.push(
            new MenuItem({
                caption: '🛡️株価を更新',
                name: 'update-stock-price',
                action: () => {
                    if (confirm('この銘柄の株価を更新しますか？')) document.getElementById('update-stock-price').submit();
                }
            })
        );

        items.push(
            new MenuItem({
                caption: '🛡️編集',
                name: 'edit-stock',
                action: () => {
                    location.href=`${BASE_PATH}/stocks/edit/${stockId}`;
                }
            })
        );

        items.push(
            new MenuItem({
                caption: '🛡️削除',
                name: 'delete-stock',
                action: () => {
                    if (confirm('この銘柄を削除しますか？')) document.getElementById('delete-stock').submit();
                }
            })
        );
    }

    if (user) {
        items.push(
            new MenuItem({
                caption: 'ログアウト',
                name: 'logout',
                action: () => document.getElementById('logout').submit()
            })
        );
        
    } else {
        items.push(
            new MenuItem({
                caption: 'ログイン',
                name: 'login',
                action: () => location.href = `${BASE_PATH}/show_login`
            })
        );
    }

    const menu = new Menu({
        menuBtnId: 'menu-btn',
        menuPanelId: 'menu-panel',
        items
    });

    menu.init();
}

function initView(user) {
    // ユーザー権限ごとの要素の表示非表示設定
    for (dom of document.getElementsByClassName("user-valid")) {
        dom.classList.toggle("hidden", !user)
    }
}
