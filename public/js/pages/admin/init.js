import { BASE_PATH } from '../../config.js';
import { getCsrfToken } from '../../utils/common.js';
import { MenuItem } from '../../utils/menu-item.js';
import { Menu } from '../../utils/menu.js';
import { StocksViewModule, ViewType } from '../../utils/stocks-view.js';
import * as showModule from './show.js'


let stockView;
let selectedStockId = null; 
let inputSymbol = '';
let isViewTentativeOnly = false;
const optionItems = [];

const MODAL_MODE = Object.freeze({
    REGISTER: "register",
    EDIT: "edit"
});
let modalMode = MODAL_MODE.REGISTER;

document.addEventListener("DOMContentLoaded", () => {
    init();
});

async function init() {
    await loadStockListFile();

    initMenu();

    stockView = new StocksViewModule();
    initEventsFromStockView();
    initModalScreenEvents();

    initView();

    document.getElementById('manual-input-btn').addEventListener('click', ()=>{
        const stockId = document.getElementById('modal-form-stock-id').value;
        location.href = `${BASE_PATH}/manual-inputs?stock_id=${stockId}`;
    });

    document.getElementById('search-input-clear-btn').addEventListener('click', ()=>{
        document.getElementById('input-symbol').value = '';
        inputSymbol = '';
        showSearchedStockes();
    });

}


function initView() {
    for (const stock of stocks) {
        optionItems.push({stock_id: stock.id, value: `${stock.symbol}: ${stock.name}`});
    }

    showSearchedStockes();

    document.getElementById('input-symbol').addEventListener('input', function () {
        inputSymbol = this.value;
        showSearchedStockes();
    });

    document.getElementById('view-tentative-only').addEventListener('change', function () {
        isViewTentativeOnly = this.checked;
        console.log(isViewTentativeOnly);
        showSearchedStockes();
    });
}

function showSearchedStockes() {
    const filterdOptionItems = (inputSymbol === '')? 
        optionItems :
        optionItems.filter(item => item.value.includes(inputSymbol));

    if (filterdOptionItems.length > 0 ) {
        const searchedStocks = [];
        for (const item of filterdOptionItems) {
            const stock = stocks.find(s => s.id === item.stock_id);
            if (isViewTentativeOnly && !stock.tentative) continue;
            const s = {
                id: stock['id'],
                name: stock['name'],
                symbol: stock['symbol'],
                tentative: stock['tentative'],
                date: stock['latest_date'],
                price: stock['latest_close']
            }
            searchedStocks.push(s);
        }

        if (searchedStocks.length > 0) {
            showModule.setTableData(searchedStocks);
            showModule.showTable();
        } else {
            const textElement = document.createElement('p');
            textElement.textContent = `該当する銘柄がありません。`;
     
            const container = document.getElementById("searched-stock-list");
            container.innerHTML = '';
            container.appendChild(textElement);
        }
        
    } else {
       const textElement = document.createElement('p');
       textElement.textContent = `${inputSymbol}は登録されていません。登録しますか？`;
       const buttonElement = document.createElement('button');
       buttonElement.textContent = '登録';
       buttonElement.addEventListener('click', (e) => {
            e.stopPropagation();
            registrateNewStock(inputSymbol);
        })

       const container = document.getElementById("searched-stock-list");
       container.innerHTML = '';
       container.appendChild(textElement);
       container.appendChild(buttonElement);
    }

}

async function registrateNewStock(inputSymbol) {
    console.log(inputSymbol);
    const yFinanceData = await getYFinanceData(inputSymbol);
    console.log(yFinanceData);


    // モーダル画面にデータを設定して、銘柄編集画面を表示
    modalMode = MODAL_MODE.REGISTER;
    // document.getElementById('modal-symbol').textContent = targetStock["コード"];
    document.getElementById('input-stock-symbol').value = inputSymbol;
    document.getElementById('input-stock-symbol').disabled = false;
    document.getElementById('modal-form-symbol').value = inputSymbol;
    document.getElementById('input-stock-name').value = "";
    document.getElementById('input-digit').value = 0;
    document.getElementById('modal-form-stock-id').value = "";
    document.getElementById('modal-submit').textContent = "登録";

    showModalMessages([]);
    document.querySelector(".modal").classList.remove("hidden");
}

async function getYFinanceData(symbol) {

    try {
        const params = new URLSearchParams({ keywords: symbol });
        const res = await fetch(`${BASE_PATH}/api/admins/show?${params}`, {
            headers: { Accept: 'application/json' }
        });

        if (!res.ok) {
            return 'server access error';
        } 

        const data = await res.json();
        if (!data.success) {
            return 'not found';
        }

        return data.data;
        
    } catch (e) {
        return 'server access error';
    } 
}












let jpxStockDataList = [];
async function loadStockListFile() {

    const res = await fetch(`${BASE_PATH}/resources/data/data_j.xls`);
    const arrayBuffer = await res.arrayBuffer();
    const data = new Uint8Array(arrayBuffer);
    const workbook = XLSX.read(data, { type: 'array' });
    const sheet = workbook.Sheets[workbook.SheetNames[0]];
    jpxStockDataList = XLSX.utils.sheet_to_json(sheet);
}

function initMenu() {
    const items = [];

    if (isAdmin) {
        items.push(
            new MenuItem({
                caption: '🛡️全ての銘柄の株価を更新',
                name: 'update-stock-prices-all',
                action: () => {
                    if (confirm('全銘柄の最新の株価を追加しますか？')) document.getElementById('update-stock-prices-all').submit();
                }
            })
        );
    }

    items.push(
        new MenuItem({
            caption: 'ログアウト',
            name: 'logout',
            action: () => document.getElementById('logout').submit()
        })
    );

    const menu = new Menu({
        menuBtnId: 'menu-btn',
        menuPanelId: 'menu-panel',
        items
    });

    menu.init();
}

// stockViewModuleからのイベントを受ける
function initEventsFromStockView() {
    // 登録
    document.addEventListener("register-stock", async (e) => {
        const { symbol } = e.detail;

        console.log("register-stockイベントを受け取りました:", symbol);

        showMessages([{ message: '検索中...', type: 'nomal' }]);

        console.log(typeof jpxStockDataList[0]["コード"], typeof symbol);

        const targetStock = jpxStockDataList.find(row => row["コード"] === symbol);
        if (!targetStock) {
            showSearchResult(['銘柄が見つかりませんでした'], null);
            return;
        }

        try {
            const params = new URLSearchParams({ keywords: symbol + '.T' });

            const res = await fetch(`${BASE_PATH}/api/admins/show?${params}`, {
                headers: { Accept: 'application/json' }
            });

            if (!res.ok) {
                showSearchResult([`サーバーエラー（${res.status}）`], null);
                return;
            }

            const data = await res.json();

            if (!data.success) {
                showSearchResult(data.errors, null);
                return;
            }

            // showSearchResult([], data.data, data.isRegistered);


            // モーダル画面にデータを設定して、銘柄編集画面を表示
            console.log(data.data);
            modalMode = MODAL_MODE.REGISTER;
            // document.getElementById('modal-symbol').textContent = targetStock["コード"];
            document.getElementById('input-stock-symbol').value = targetStock["コード"];
            document.getElementById('modal-form-symbol').value = targetStock["コード"];
            document.getElementById('input-stock-name').value = targetStock["銘柄名"];
            document.getElementById('input-digit').value = judgeDigit([data.data.open, data.data.high, data.data.low, data.data.close]);
            document.getElementById('modal-form-stock-id').value = "";
            document.getElementById('modal-submit').textContent = "登録";

            showModalMessages([]);
            document.querySelector(".modal").classList.remove("hidden");


        } catch (e) {
            showSearchResult(['通信エラーが発生しました'], null);
        } finally {
            showMessages([]);
        }

        // const url = `${BASE_PATH}/api/stocks/update-stock-prices`;
        // try {
        //     const formData = new FormData();
        //     formData.append('csrf_token', getCsrfToken());
        //     formData.append('stockId', stockId);

        //     const res = await fetch(url, {
        //         method: 'POST',
        //         body: formData,
        //         credentials: 'same-origin', // セッション / CSRF用
        //     });

        //     if (!res.ok) {
        //         throw new Error('通信エラー');
        //     }

        //     const result = await res.json();

        //     if (!result.success) throw new Error('書き込みエラー');

        //     alert('株価を更新しました');

        // } catch (err) {
        //     console.error(err);
        //     alert('株価更新に失敗しました');
        // }
    });

    // 分割情報更新＆株価更新
    document.addEventListener("update-prices", async (e) => {
        const { stockId } = e.detail;

        // // 分割情報更新
        // const url2 = `${BASE_PATH}/api/splits/store`;
        // try {
        //     const formData = new FormData();
        //     formData.append('csrf_token', getCsrfToken());
        //     formData.append('stock_id', stockId);

        //     const res = await fetch(url2, {
        //         method: 'POST',
        //         body: formData,
        //         credentials: 'same-origin', // セッション / CSRF用
        //     });

        //     if (!res.ok) {
        //         throw new Error('分割情報更新時に通信エラー発生');
        //     }

        //     const result = await res.json();
        //     console.log("分割情報更新のAPIエラー:", result.errors);

        //     if (!result.success) throw new Error('分割情報更の更新に失敗');

        //     // alert('株価を更新しました');

        // } catch (err) {
        //     console.error(err);
        //     // alert('株価更新に失敗しました');
        // }

        // 株価更新
        const url = `${BASE_PATH}/api/stocks/update-stock-prices`;
        try {
            const formData = new FormData();
            formData.append('csrf_token', getCsrfToken());
            formData.append('stock_id', stockId);

            const res = await fetch(url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin', // セッション / CSRF用
            });

            if (!res.ok) {
                throw new Error('通信エラー');
            }

            const result = await res.json();

            if (!result.success) throw new Error('書き込みエラー');

            alert('株価を更新しました');

        } catch (err) {
            console.error(err);
            alert('株価更新に失敗しました');
        }
    });

    document.addEventListener("edit-stock", async (e) => {
        const { stockId } = e.detail;

        // 編集用画面に表示するために、現在の登録情報をAPIから取得
        let stock = null;
        try {
            const res = await fetch(`${BASE_PATH}/api/stocks/get/${stockId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!res.ok) {
                throw new Error('通信エラー');
            }

            const result = await res.json();
            if (!result.success) throw new Error('書き込みエラー');

            stock = result.stock;

        } catch (err) {
            console.error(err);
            return;
        }

        // モーダル画面にデータを設定して、銘柄編集画面を表示
        modalMode = MODAL_MODE.EDIT;
        // document.getElementById('modal-symbol').textContent = stock.symbol;
        document.getElementById('input-stock-symbol').value = stock.symbol;
        document.getElementById('input-stock-name').value = stock.name;
        document.getElementById('input-digit').value = stock.digit;
        document.getElementById('modal-form-stock-id').value = stockId;
        document.getElementById('modal-submit').textContent = "更新";

        showModalMessages([]);
        document.querySelector(".modal").classList.remove("hidden");
    });

    // 登録銘柄削除
    document.addEventListener("remove-stock", async (e) => {
        if (!confirm('この銘柄を削除しますか？')) return;

        const { stockId } = e.detail;
        const url = `${BASE_PATH}/api/stocks/delete`;
        try {
            const formData = new FormData();
            formData.append('csrf_token', getCsrfToken());
            formData.append('stockId', stockId);

            const res = await fetch(url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin', // セッション / CSRF用
            });

            if (!res.ok) {
                throw new Error('通信エラー');
            }

            const result = await res.json();

            if (!result.success) throw new Error('書き込みエラー');
            showSearchedStockes();

        } catch (err) {
            console.error(err);
            alert('削除に失敗しました');
        }
    });

    // 銘柄詳細画面に遷移
    document.addEventListener("show-detail", (e) => {
        const { stockId } = e.detail;
        const redirectUri = encodeURI(`${BASE_PATH}/admins`);
        location.href = `${BASE_PATH}/stocks/show-detail/${stockId}?redirect=${redirectUri}`
    });
}

function initModalScreenEvents() {
    // モーダル画面の閉じるボタン
    document.querySelector(".modal-close").addEventListener("click", () => {
        document.querySelector(".modal").classList.add("hidden");
    });

    // モーダル画面の更新ボタンを押した時の処理
    document.getElementById('modal-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);

        // バリデーションチェック
        const symbol = formData.get('symbol');
        const name = formData.get('name');
        const digit = formData.get('digit');
        const validationErrors = [];

        console.log("バリデーションチェック:", { name, digit });

        if (name === "") validationErrors.push("名前を入力して下さい");
        if (name.length > 255) validationErrors.push("名前は255文字以下で入力して下さい");
        if (!(/^\d+$/.test(digit))) validationErrors.push("桁数は正の整数を入力してください");

        if (validationErrors.length > 0) {
            showModalMessages(validationErrors.map(err => ({ 'message': err, 'type': 'error' })));
            return;
        }

        const csrfToken = getCsrfToken();
        formData.append('csrf_token', csrfToken);
        
        showModalMessages([]);

        if (modalMode === MODAL_MODE.REGISTER) {
            // 登録処理
            try {
                const url = `${BASE_PATH}/api/stocks/store`;
                const res = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin', // セッション / CSRF用
                });

                if (!res.ok) {
                    throw new Error('通信エラー');
                }

                const result = await res.json();
                if (!result.success) {
                    throw new Error('登録エラー');
                }

                alert('登録しました');
                location.reload();

            } catch (err) {
                console.error(err);
                alert('登録に失敗しました');
            }

        } else {
            // 更新処理
            const url = `${BASE_PATH}/api/stocks/update`;
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin', // セッション / CSRF用
                });

                if (!res.ok) {
                    throw new Error('通信エラー');
                }

                const result = await res.json();

                if (!result.success) throw new Error('書き込みエラー');

                // 画面更新処理
                alert('更新しました');
                location.reload();

            } catch (err) {
                console.error(err);
                alert('更新に失敗しました');
            }
        }

        document.querySelector(".modal").classList.add("hidden");
    });
}

function showModalMessages(messageObjects) {  // messageObjects: {message: string, type: string(error/success)}
    const messageContainer = document.getElementById("modal-message-container");
    messageContainer.innerHTML = '';
    for (const obj of messageObjects) {
        const element = document.createElement('p');
        element.textContent = obj.message;
        element.className = obj.type;
        messageContainer.appendChild(element);
    }
}

function showSearchResult(errors, data, isRegistered = false) {
    const resultContainer = document.querySelector(".content-container");

    if (errors?.length) {
        showMessages(errors.map(err => ({ message: err, type: 'error' })));
        resultContainer.classList.add("hidden");
        return;
    }

    if (!data) {
        showMessages([{ message: '検索結果がありません。', type: 'nomal' }]);
        resultContainer.classList.add("hidden");
        return;
    }

    resultContainer.classList.remove("hidden");
    document.getElementById('formSubmit').toggleAttribute('disabled', isRegistered);
    showMessages(isRegistered ? [{ message: 'この銘柄はすでに登録されています', type: 'error' }] : []);

    const fields = {
        'result-symbol': data.symbol,
        'result-date': data.date,
        'result-open': Number(data.open).toLocaleString('ja-JP'),
        'result-high': Number(data.high).toLocaleString('ja-JP'),
        'result-low': Number(data.low).toLocaleString('ja-JP'),
        'result-close': Number(data.close).toLocaleString('ja-JP'),
        'result-volume': Number(data.volume).toLocaleString('ja-JP'),
    };

    Object.entries(fields).forEach(([id, value]) => {
        document.getElementById(id).textContent = value;
    });

    const inputs = {
        name: data.shortName,
        digit: judgeDigit([data.open, data.high, data.low, data.close]),
        symbol: data.symbol,
        short_name: data.shortName,
        long_name: data.longName,
    };

    Object.entries(inputs).forEach(([id, value]) => {
        document.getElementById(id).value = value;
    });
}

function showMessages(messageObjects) {  // messageObjects: {message: string, type: string(error/success)}
    const messageContainer = document.getElementById("message-container");
    messageContainer.innerHTML = '';
    for (const obj of messageObjects) {
        const element = document.createElement('p');
        element.textContent = obj.message;
        element.className = obj.type;
        messageContainer.appendChild(element);
    }
}

function judgeDigit(numberArray) {
    let maxDecimalPointLength = 0;
    const getDecimalPointLength = (number) => {
        const numbers = String(number).split('.');
        return numbers[1] ? numbers[1].length : 0;
    }

    for (const number of numberArray) {
        const decimalPointLength = getDecimalPointLength(number);
        if (maxDecimalPointLength < decimalPointLength) maxDecimalPointLength = decimalPointLength;
    }

    if (maxDecimalPointLength > 2) maxDecimalPointLength = 2;

    return maxDecimalPointLength;
}
