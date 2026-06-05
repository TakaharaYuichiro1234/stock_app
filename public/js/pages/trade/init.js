import { BASE_PATH } from '../../config.js';
import { MenuItem } from '../../utils/menu-item.js';
import { Menu } from '../../utils/menu.js';
import { getCsrfToken } from '../../utils/common.js';
import * as store from './store.js';

let trades = [];
let inputData;

document.addEventListener("DOMContentLoaded", () => {
    init();
});


async function init() {
    initMenu();
    initInputData();
    initRegistrationEvents();
    initModal();
    
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
    const typeText = {1: '買付', 2: '売付', 3: '配当'};
    const dataTable = document.getElementById('data-table');
    dataTable.innerHTML = "";

    for (const trade of trades) {
        const td1 = document.createElement('td');
        td1.textContent = trade['date'];
        const td2 = document.createElement('td');
        td2.textContent = typeText[trade['type']];
        const td3 = document.createElement('td');
        td3.textContent = trade['symbol'];
        const td4 = document.createElement('td');
        td4.textContent = trade['name'];
        const td5 = document.createElement('td');
        td5.textContent = trade['content'];
        const td6 = document.createElement('td');
        td6.textContent = (trade['type'] === 1 || trade['type'] === 2) ? trade['quantity']: "---";
        const td7 = document.createElement('td');
        td7.textContent = (trade['type'] === 1 || trade['type'] === 2) ? trade['price']: "---";
        const td8 = document.createElement('td');
        td8.textContent = (trade['type'] === 1 || trade['type'] === 2) ? trade['quantity']*trade['price'] : trade['subtotal'];

        const tr = document.createElement('tr');
        tr.appendChild(td1);
        tr.appendChild(td2);
        tr.appendChild(td3);
        tr.appendChild(td4);
        tr.appendChild(td5);
        tr.appendChild(td6);
        tr.appendChild(td7);
        tr.appendChild(td8);

        dataTable.appendChild(tr);
    }

}




function initModal() {

    // モーダル画面表示・非表示
    document.getElementById("batch-input-button").addEventListener("click", () => {
        // document.getElementById('modal-update').classList.add("hidden");
        // document.getElementById('modal-submit').classList.remove("hidden");

        document.querySelector(".modal").classList.remove("hidden");
    });

    document.querySelector(".modal-close").addEventListener("click", () => {
        document.querySelector(".modal").classList.add("hidden");
    });

    document.getElementById("add-to-temporary-data").addEventListener("click", () => {
        store.addToTemporaryData();
        document.querySelector(".modal").classList.add("hidden");
    });
}

// ===========================================================
// 新規取引データ登録関連
// ===========================================================
function initRegistrationEvents() {
    document.getElementById('temporary-save').addEventListener('click', function() {
        store.getInputData();
    });

    // 「貼り付け」クリック時の処理
    document.getElementById('paste-from-clipboard').addEventListener('click', async () => {
        await store.pasteFromClipboard();
    });

    document.getElementById('store-button').addEventListener('click', async () => {
        await store.storeData();
    });
}

function initInputData() {

    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0'); 
    const dd = String(today.getDate()).padStart(2, '0');
    document.getElementById('input-date').value = `${yyyy}-${mm}-${dd}`;


    for (const stock of userStocks) {
        const option = document.createElement('option');
        option.value = `${stock.symbol}: ${stock.name}`;
        option.dataset.id = stock.symbol;
        document.getElementById('symbol-select').appendChild(option);
    }

    const input = document.getElementById('input-symbol');
    input.addEventListener('input', function () {
        const nameView = document.getElementById('matched-name');
        nameView.textContent = "";
        if (!this.value) return;

        const option = document.querySelector(
            `#symbol-select option[value="${this.value}"]`
        );

        let targetVal = this.value;
        if (option) {
            targetVal = option.dataset.id;
            this.value = targetVal;            
        }
        const value = stocks.find(stock => stock.symbol === targetVal)?.name || "該当なし";
        nameView.textContent = value;
    });


    for (const account of accounts) {
        const option = document.createElement('option');
        option.value = `${account.content}`;
        option.dataset.id = account.id;
        document.getElementById('account-select').appendChild(option);
    }

    // document.getElementById('input-account').addEventListener('input', function () {
    //     if (!this.value) return;

    //     const option = document.querySelector(
    //         `#account-select option[value="${this.value}"]`
    //     );

    //     let targetVal = this.value;
    //     if (option) {
    //         targetVal = option.dataset.id;
    //         this.value = targetVal;            
    //     }

    // });



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




