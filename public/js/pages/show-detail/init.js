let chart;

async function init() {
    initMenu();
    initView();
 
    // // この銘柄の株価データ取得
    // let pricesData = null;
    // try {
    //     const res = await fetch(`${BASE_PATH}/api/stocks/get_for_chart/${stockId}`);
    //     if (!res.ok) throw new Error('APIエラー');
    //     const json = await res.json();
    //     pricesData = json.data;
    // } catch (err) {
    //     console.error(err);
    //     return;
    // }

    // // このユーザーがこの銘柄に登録した取引データ取得
    // let tradeData = [];
    // if (user){
    //     try {
    //         const res = await fetch(`${BASE_PATH}/api/trades/get_for_chart/${user['uuid']}/${stockId}`);
    //         if (!res.ok) throw new Error('APIエラー');
    //         const json = await res.json();
    //         tradeData = json.data;

    //         console.log("tradeData: ", tradeData);
    //     } catch (err) {
    //         console.error(err);
    //         return;
    //     }
    // }

    initChart();
    initModal();
    initOtherEvents();
}

function initMenu() {
    const items = [];

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

function initView() {
    // ユーザー権限ごとの要素の表示非表示設定
    for (dom of document.getElementsByClassName("user-valid")) {
        dom.classList.toggle("hidden", !user)
    }
}

function initChart() {
    // 株価チャート初期化
    chart = new ChartModule('chart');
    chart.init();
    chart.drawChart(chartPrices['daily'], chartTrades['daily']?? []);
    
    // 日足・週足・月足の初期化
    const granularity = localStorage.getItem('stock-app:chart-granularity') || 'daily';
    const selectChart = document.getElementById('select-chart');
    selectChart.accessibleradio.value = granularity;
    const monthRange = (granularity === "monthly") ? 48 : (granularity === "weekly") ? 12 : 3; 
    chart.drawChart(chartPrices[granularity], chartTrades[granularity]?? [], monthRange);

    // 日足・週足・月足の変更イベント登録
    selectChart.addEventListener('change', () => {
        const granularity = selectChart.accessibleradio.value;
        const monthRange = (granularity === "monthly") ? 48 : (granularity === "weekly") ? 12 : 3; 
        chart.drawChart(chartPrices[granularity], chartTrades[granularity]?? [], monthRange);
        localStorage.setItem('stock-app:chart-granularity', granularity);
    });

    // チャートをクリックした時のカスタムイベント
    document.addEventListener("click-chart", (e) => {
        const { time } = e.detail;
    
        const chartType = selectChart.accessibleradio.value;
        const prices = chartPrices[chartType].find( p => p.time ===  time);

        // チャートクリック時の値を表示する各要素に値を入力
        showChartClickedData(time, prices);

        // モーダル画面の各要素にも入力
        setModalPriceTable(time, prices);
    });

}

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

function initModal() {

    // モーダル画面表示・非表示
    document.getElementById("show-modal-button").addEventListener("click", () => {
        document.getElementById('modal-update').classList.add("hidden");
        document.getElementById('modal-submit').classList.remove("hidden");

        document.querySelector(".modal").classList.remove("hidden");
    });

    document.querySelector(".modal-close").addEventListener("click", () => {
        document.querySelector(".modal").classList.add("hidden");
    });

    // モーダル画面内の日付を変更した時のイベント
    const inputDate = document.getElementById("input-date");
    inputDate.addEventListener("change", () => {
        const date = inputDate.value;
        // const chartType = selectChart.accessibleradio.value;
        // const prices = chartPrices[chartType].find(p => p.time ===  date);
        const prices = chartPrices['daily'].find(p => p.time ===  date);

        setModalPriceTable(date, prices);
    });

    // モーダル画面でsubmitされたとき(新規取引登録)
    document.getElementById('modal-submit').addEventListener('click', (event) => {
        event.preventDefault();
        const actionUrl = `${BASE_PATH}/trades/store`;

        const form = document.getElementById('modal-form');
        form.action = actionUrl;
        form.submit();
    });

    // モーダル画面でsubmitされたとき(取引更新)
    document.getElementById('modal-update').addEventListener('click', (event) => {
        event.preventDefault();
        const actionUrl = `${BASE_PATH}/trades/update`;

        const form = document.getElementById('modal-form');
        form.action = actionUrl;
        form.submit();
    });
}

function edit(tradeId){
    console.log(tradeId);
    const index = trades.findIndex(trade => trade.id === tradeId);
    if (index>=0) {
        const trade = trades[index];
        
        const date = trade.date;
        // const prices = trade

        console.log(trade);

        // const chartType = selectChart.accessibleradio.value;
        const prices = chartPrices['daily'].find(p => p.time ===  date);

        setModalPriceTable(date, prices);
        setModalEditingData(trade.price, trade.quantity, trade.type, trade.content);
        document.getElementById('modal-update').classList.remove("hidden");
        document.getElementById('modal-submit').classList.add("hidden");

        document.querySelector(".modal").classList.remove("hidden");

        document.getElementById('trade-id-for-update').value = tradeId
    }
}

function setModalPriceTable(date, prices) {
    // 1. チャートクリック時
    // 2. モーダル画面内の日付を変更した時
    // 3. 登録済みの取引データの編集時

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
function setModalEditingData(price, quantity, type, content) {
    document.getElementById('input-price').value = price;
    document.getElementById('input-quantity').value = quantity;
    document.getElementById('modal-input-type').value = type;
    document.getElementById('input-content').value = content;



}



function deleteTrade(tradeId){
    console.log(tradeId);
    if (!confirm("この取引データを削除してもよろしいですか？")) return;

    document.getElementById('trade-id-for-delete').value = tradeId;


    const actionUrl = `${BASE_PATH}/trades/delete`;

    const form = document.getElementById('delete-trade');
    form.action = actionUrl;
    form.submit();

}

function initOtherEvents() {
    // ウインドウサイズ変更時のイベント
    window.addEventListener('resize', () => {
        // 画面サイズを変更した時にチャートのサイズを再調整
        chart.resizeChart(document.getElementById('chart').clientWidth, document.getElementById('chart').clientHeight);
    });
}
