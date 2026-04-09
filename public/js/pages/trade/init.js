import { BASE_PATH } from '../../config.js';
import { MenuItem } from '../../utils/menu-item.js';
import { Menu } from '../../utils/menu.js';
import { getCsrfToken } from '../../utils/common.js';



let trades = [];
let inputData;
document.addEventListener("DOMContentLoaded", () => {
    init();
});


async function init() {
    initMenu();
    initRegistrationEvents();
    

    trades = await fetchData();

    showData();

}

// ===========================================================
// 登録済みの取引データ表示・編集関連
// ===========================================================
async function fetchData() {
    const res = await fetch(`${BASE_PATH}/api/trades`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        }
    });

    if (!res.ok) {
        alert('検索に失敗しました');
        return;
    }

    const json = await res.json();
    const trades = json.trades;
    return trades;
}

function showData() {
    const dataTable = document.getElementById('data-table');
    for (const trade of trades) {
        const td1 = document.createElement('td');
        td1.textContent = trade['date'];
        const td2 = document.createElement('td');
        td2.textContent = trade['type'];
        const td3 = document.createElement('td');
        td3.textContent = trade['stock_id'];
        const td4 = document.createElement('td');
        td4.textContent = "仮"
        const td5 = document.createElement('td');
        td5.textContent = trade['account_id'];
        const td6 = document.createElement('td');
        td6.textContent = trade['quantity'];
        const td7 = document.createElement('td');
        td7.textContent = trade['price'];

        const tr = document.createElement('tr');
        tr.appendChild(td1);
        tr.appendChild(td2);
        tr.appendChild(td3);
        tr.appendChild(td4);
        tr.appendChild(td5);
        tr.appendChild(td6);
        tr.appendChild(td7);

        dataTable.appendChild(tr);
    }

}



// ===========================================================
// 新規取引データ登録関連
// あとでModalに移す
// ===========================================================
function initRegistrationEvents() {
    // 「貼り付け」クリック時の処理
    document.getElementById('paste-from-clipboard').addEventListener('click', async () => {
        await pasteFromClipboard();
    });

    // document.getElementById('check-symbols').addEventListener('click', async () => {
    //     await checkSymbols();
    // });

    document.getElementById('store-button').addEventListener('click', async () => {
        if (!inputData) {
            alert("データがありません");
            return;
        } else {
            await checkSymbols();
            await storeData();
        }
    });
}

async function pasteFromClipboard() {
    const clipboardData = await navigator.clipboard.readText();
    inputData = parseExcelClipboard(clipboardData);

    const dataTable = document.getElementById('data-paste-table');
    for (const row of inputData) {
        const tr = document.createElement('tr');
        for (const cell of row) {
            const td = document.createElement('td');
            td.textContent = cell;
            tr.appendChild(td);
        }
        dataTable.appendChild(tr);
    }
}

function parseExcelClipboard(text) {
    const rows = [];
    let row = [];
    let cell = '';

    let i = 0;
    let inQuotes = false;

    while (i < text.length) {
        const char = text[i];
        const nextChar = text[i + 1];

        // --- ダブルクォート処理 ---
        if (char === '"') {
            if (inQuotes && nextChar === '"') {
                // "" → エスケープされた "
                cell += '"';
                i += 2;
                continue;
            } else {
                // クォート開始 or 終了
                inQuotes = !inQuotes;
                i++;
                continue;
            }
        }

        // --- タブ（列区切り） ---
        if (char === '\t' && !inQuotes) {
            row.push(cell);
            cell = '';
            i++;
            continue;
        }

        // --- 改行（行区切り） ---
        if ((char === '\n') && !inQuotes) {
            row.push(cell);
            rows.push(row);

            row = [];
            cell = '';
            i++;
            continue;
        }

        // --- CR除去（Windows対策） ---
        if (char === '\r') {
            i++;
            continue;
        }

        // --- 通常文字 ---
        cell += char;
        i++;
    }

    // 最後のセル・行
    if (cell !== '' || row.length > 0) {
        row.push(cell);
        rows.push(row);
    }

    return rows;
}

async function checkSymbols() {
    const temtativeRegistrated = [];
    const registered = [];
    for (const row of inputData) {
        if (row[4] === "") continue;   // 空行はスキップ
        // console.log("symbol: ", row[4]);
        console.log("row: ", row);
        const symbol = row[4].toUpperCase();
        if (!(/^[A-Z0-9]{4}$/.test(symbol))) continue;   // 銘柄コードの形式でないものはスキップ{
        const name = row[3].trim();

        const ret = await tentativelyStoreStock(symbol, name);

        if (ret) {
            temtativeRegistrated.push(symbol);
        } else {
            registered.push(symbol);
        }
    }

    const unregisteredDiv = document.getElementById('unregistered-symbols');

    const msg = document.createElement('p');
    msg.textContent = "以下の銘柄は仮登録されました。後ほど管理者が確認して正式に登録されます。";
    unregisteredDiv.appendChild(msg);

    for (const symbol of temtativeRegistrated) {
        const p = document.createElement('p');
        p.textContent = symbol;
        unregisteredDiv.appendChild(p);
    }
}

async function tentativelyStoreStock(symbol, name) {
    const url = `${BASE_PATH}/api/stocks/tentative-store`;

    try {
        const formData = new FormData();
        formData.append('csrf_token', getCsrfToken());
        formData.append('symbol', symbol + '.T');
        formData.append('name', name);

        const res = await fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin', // セッション / CSRF用
        });

        if (!res.ok) {
            throw new Error('通信エラー');
        }

        const result = await res.json();
        if (result.success) {
            console.log("stockId: ", result.data['stockId']);
            return true;
        }

    } catch (err) {
        console.error(err);
    }
    return false;
}

async function storeData() {
    const verifiedData = [];
    for (const row of inputData) {
        const verifiedRowData = verifyData(row);

        if (Object.keys(verifiedRowData).length === 6) {
            verifiedData.push(verifiedRowData);
        }
    }

    if (!confirm("登録しますか？")) return;

    const data = JSON.stringify(verifiedData);
    const url = `${BASE_PATH}/api/trades/store`;

    try {
        const formData = new FormData();
        formData.append('csrf_token', getCsrfToken());
        formData.append('input_trades', data);

        const res = await fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin', // セッション / CSRF用
        });

        console.log(res);

        if (!res.ok) {
            throw new Error('通信エラー');
        }

        const result = await res.json();
        console.log(result);

        // currentStockIdList = stockView.getUsersStockIdList();
        alert('登録しました');

    } catch (err) {
        console.error(err);
    }
}




function verifyData(row) {
    const verifiedData = {};
    if (row.length < 8) return {};

    // 日付	年	分類	銘柄	証券コード	口座	株数	株価

    // 日付
    if (new Date(row[0]).getTime()) verifiedData['date'] = row[0];

    // 銘柄コード
    if (/^[A-Z0-9]{4}$/.test(row[4].toUpperCase())) {
        verifiedData['symbol'] = row[4].toUpperCase();
    }

    if (row[5] !== "") verifiedData['account_name'] = row[5].trim();


    if (row[2] === "買付" || row[2] === "売付") verifiedData['type_name'] = row[2];


    if (!isNaN(row[6]) && row[6].trim() !== "") verifiedData['quantity'] = row[6];


    if (!isNaN(row[7].replace(/,/g, '')) && row[7].trim() !== "") verifiedData['price'] = row[7].replace(/,/g, '');

    return verifiedData;

}


// ===========================================================
// Menu
// ===========================================================
function initMenu() {
    const items = [];

    // if (isAdmin) {
    //     items.push(
    //         new MenuItem({
    //             caption: '🛡️管理画面',
    //             name: 'admin',
    //             action: () => location.href = `${BASE_PATH}/admins`
    //         })
    //     );
    // }

    if (user) {
        items.push(
            new MenuItem({
                caption: 'お気に入り銘柄編集',
                name: 'user-stock',
                action: () => location.href = `${BASE_PATH}/user-stocks`
            }),
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




