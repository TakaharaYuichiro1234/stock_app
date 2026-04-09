import { BASE_PATH } from '../../config.js';
import { MenuItem } from '../../utils/menu-item.js';
import { Menu } from '../../utils/menu.js';


let currentSort = {
    key: 'profitAndLoss', // 初期ソート
    asc: false
};

const headers = [
    { label: '証券コード', key: 'symbol' },
    { label: '銘柄名', key: 'name' },
    { label: '数量', key: 'total_quantity' },
    { label: '平均取得価格', key: 'average_price' },
    { label: '取得金額', key: 'perchasedValue' },
    { label: '評価額', key: 'evaluatedValue' },
    { label: '損益', key: 'profitAndLoss' }
];

function sortTrades(data, key, asc) {
    return data.sort((a, b) => {
        let aValue = a[key];
        let bValue = b[key];

        // tentative対応
        if (key === 'profitAndLoss' || key === 'evaluatedValue') {
            aValue = a.tentative === 1 ? -Infinity : aValue;
            bValue = b.tentative === 1 ? -Infinity : bValue;
        }

        if (typeof aValue === 'string') {
            return asc ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
        }

        return asc ? aValue - bValue : bValue - aValue;
    });
}




document.addEventListener("DOMContentLoaded", () => {
    init();
});

function init() {
    initEvents();
    initMenu();

    renderTradeSummary();

    showAssets();
}

function initEvents() {
    // for (const stock of stocks) {
    //     const id = stock['id'];
    //     document.getElementById(`list-content_${id}`).addEventListener('click', () => {
    //         window.location.href = `${BASE_PATH}/stocks/show-detail/${id}`;
    //     })
    // }
}

function initMenu() {
    const items = [];

    if (isAdmin) {
        items.push(
            new MenuItem({
                caption: '🛡️管理画面',
                name: 'admin',
                action: () => location.href = `${BASE_PATH}/admins`
            })
        );
    }

    if (user) {
        items.push(
            new MenuItem({
                caption: '取引データ入力',
                name: 'input-trade',
                action: () => location.href = `${BASE_PATH}/trades`
            }),
            // new MenuItem({
            //     caption: 'お気に入り銘柄編集',
            //     name: 'user-stock',
            //     action: () => location.href = `${BASE_PATH}/user-stocks`
            // }),
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

// 口座ごとの集計結果を返す
function tradeSummaryFilterByAccountId(accountId) {
    const filterd = tradeSummary.filter(trade => trade.account_id === accountId);
    const result = filterd.map(trade => {
        const targetStock = stocks.find(stock => stock.id === trade.stock_id);
        const obj = {
            stock_id: trade.stock_id,
            name: targetStock?.name ?? '',
            symbol: targetStock?.symbol ?? '',
            total_quantity: trade.total_quantity,
            average_price: trade.average_price,
            perchasedValue: trade.total_quantity * trade.average_price
        }
        return obj;
    })

    return result;
}

// 全ての口座を合計した集計結果を返す
function tradeSummaryTotal() {
    const resultMap = new Map();

    for (const row of tradeSummary) {
        const value = row['total_quantity'] * row['average_price'];

        if (!resultMap.has(row['stock_id'])) {
            resultMap.set(row['stock_id'], {
                'total_quantity': 0,
                'perchasedValue': 0
            });
        }

        resultMap.set(
            row['stock_id'],
            {
                'total_quantity': resultMap.get(row['stock_id'])['total_quantity'] + row['total_quantity'],
                'perchasedValue': resultMap.get(row['stock_id'])['perchasedValue'] + value
            }
        );
    }

    const result = [];
    for (const [stockId, obj] of resultMap.entries()) {
        if (obj['total_quantity'] <= 0) continue;
        const targetStock = stocks.find(stock => stock.id === stockId);
        const evaluatedValue = (targetStock?.latest_close ?? 0) * obj['total_quantity'];
        result.push({
            stock_id: stockId,
            name: targetStock?.name ?? '',
            symbol: targetStock?.symbol ?? '',
            total_quantity: obj['total_quantity'],
            average_price: obj['perchasedValue'] / obj['total_quantity'],
            perchasedValue: obj['perchasedValue'],
            evaluatedValue: evaluatedValue,
            profitAndLoss: evaluatedValue - obj['perchasedValue'],
            tentative: targetStock?.tentative ?? 0,
            digit: targetStock?.digit ?? 0
        });
    }

    return result;
}

function renderTradeSummary(accountId = 0) {
    const validAccountId = accounts.find(account => account.id === accountId) ? accountId : 0;

    const tradeSummaryForView = (validAccountId !== 0) ? tradeSummaryFilterByAccountId(validAccountId) : tradeSummaryTotal();

    // tradeSummaryForView.sort((a, b) => {
    //     const aValue = a.tentative === 1 ? -Infinity : a.profitAndLoss;
    //     const bValue = b.tentative === 1 ? -Infinity : b.profitAndLoss;

    //     return bValue - aValue;
    // });
    sortTrades(tradeSummaryForView, currentSort.key, currentSort.asc);

    
    const container = document.getElementById('trade-summary-container');
    if (tradeSummary.length === 0) {
        container.innerHTML = '<p>取引データがありません。</p>';
        return;
    }

    const table = document.createElement('table');

    // ヘッダー行
    const headerRow = document.createElement('tr');
    // const headers = ['証券コード', '銘柄名', '数量', '平均取得価格', '取得金額', '評価額', '損益'];

    // headers.forEach(text => {
    //     const th = document.createElement('th');
    //     th.textContent = text;
    //     headerRow.appendChild(th);
    // });
    headers.forEach(header => {
        const th = document.createElement('th');
        // th.textContent = header.label;
        th.textContent = header.label + (
            currentSort.key === header.key
                ? (currentSort.asc ? ' ▲' : ' ▼')
                : ''
        );

        th.style.cursor = 'pointer';

        th.addEventListener('click', () => {
            if (currentSort.key === header.key) {
                currentSort.asc = !currentSort.asc; // 同じ列なら反転
            } else {
                currentSort.key = header.key;
                currentSort.asc = true; // 列変更時は昇順スタート
            }

            renderTradeSummary(accountId); // 再描画
        });

        headerRow.appendChild(th);
    });

    table.appendChild(headerRow);

    // データ行
    for (const trade of tradeSummaryForView) {
        const row = document.createElement('tr');

        const values = [
            trade.symbol,
            trade.name,
            trade.total_quantity,
            trade.average_price.toLocaleString('ja-JP', {minimumFractionDigits: trade.digit, maximumFractionDigits: trade.digit}),
            trade.perchasedValue.toLocaleString(),
            trade.tentative === 1 ? '---' : trade.evaluatedValue.toLocaleString(),
            trade.tentative === 1 ? '---' : trade.profitAndLoss.toLocaleString(),
        ];

        values.forEach(value => {
            const td = document.createElement('td');
            td.textContent = value;
            row.appendChild(td);
        });

        table.appendChild(row);
    }

    // コンテナに追加
    container.innerHTML = '';
    container.appendChild(table);
}


async function showAssets() {
    const response = await fetch(`${BASE_PATH}/api/trades/get_daily_assets`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    });

    if (!response.ok) {
        console.log('検索に失敗しました');
        return;
    }

    const json = await response.json();
    const data = json.data;
    console.log(data);
}


