import { BASE_PATH } from '../../config.js';
import { getCsrfToken } from '../../utils/common.js';

let selectedStockId = null; 
// let dividendsOfSelectedStock = [];

export function setListener() {
    document.getElementById('add-row-button').addEventListener('click', ()=>{
        if (!selectedStockId)  return;
        addEmptyRow();
    });

    document.getElementById('add-predict-button').addEventListener('click', ()=>{
        if (!selectedStockId)  return;
        addPredict();
    });

    document.getElementById('update-button').addEventListener('click', ()=>{
        if (!selectedStockId)  return;
        update();
    });

    document.getElementById('get-dividend-button').addEventListener('click', async() => {
        if (!selectedStockId)  return;

        getDividendsFromYahooFinance(selectedStockId);
    });
}

async function getDividendsFromYahooFinance(stockId) {
    const url = `${BASE_PATH}/api/dividends/get-yfinance-dividends?stock_id=${stockId}`;
    try {
        const res = await fetch(url, {
            method: "GET",
        });

        if (!res.ok) {
            throw new Error('通信エラー');
        }

        const result = await res.json();
        if (!result.success) throw new Error('データ取得失敗');

        addDataRows(stockId, result.dividends);

    } catch (err) {
        console.error(err);
        return;
    }

}

function getTableData() {
    const tbody = document.getElementById('tableBody');
    const trs = tbody.querySelectorAll('tr');

    const dividendsOfSelectedStock = [];
    for (const tr of trs) {
        const recordDate = tr.querySelector('[name="record_date"]').value;
        const status = tr.querySelector('[name="status"]').value;
        const dps = tr.querySelector('[name="dps"]').value;
        const paymentDate = tr.querySelector('[name="payment_date"]').value;
        const saved = (tr.dataset.saved).toLowerCase() === "true";;

        const data = {
            record_date: recordDate,
            payment_date: paymentDate,
            status: status,
            stock_id: selectedStockId,
            dps: dps,
            saved: saved,
        };
        dividendsOfSelectedStock.push(data);
    }

    return dividendsOfSelectedStock;
}

async function update() {
    const tbody = document.getElementById('tableBody');
    const trs = tbody.querySelectorAll('tr');
    const updateFailedTrs = []

    for (const tr of trs) {
        if (tr.dataset.saved !== 'true') {
            const ret = await saveRow(tr);
            if (ret) {
                // 更新成功
                setSavedStatus(tr, true);
            } else {
                // 更新失敗の行は一旦保持
                updateFailedTrs.push(tr);
            }
        }
    }

    // loadData(selectedStockId);
    tbody.innerHTML = '';
    loadData(selectedStockId);
    updateFailedTrs.forEach(tr => {
        tbody.appendChild(tr);
    });

}

export function initView() {

    for (const stock of stocks) {
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

        const targetStock = stocks.find(stock => stock.symbol === targetVal);
        if (targetStock) {
            nameView.textContent = `${targetStock.name}の登録済みデータ`;
            selectedStockId = targetStock.id;
            document.getElementById('add-row-button').disabled = false;

            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';
            loadData(targetStock.id);
        } else {
            nameView.textContent = "該当なし";
            selectedStockId = null;
            document.getElementById('add-row-button').disabled = true;
        }
    });
}

async function loadData(stock_id) {
    const tbody = document.getElementById('tableBody');
    try {
        const res = await fetch(`${BASE_PATH}/api/dividends?stock_id=${stock_id}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }   
        });
        const data = await res.json();

        const dividendsOfSelectedStock = data.dividends; 
        dividendsOfSelectedStock.forEach(row => {
            const data = {
                id: row.id,
                record_date: row.record_date,
                payment_date: row.payment_date,
                status: row.status,
                stock_id: row.stock_id,
                dps: row.dps,
                saved: true,
            };
            tbody.appendChild(createRow(data));
        });

    } catch (error) {
        console.error(error);
    }   
}



function addDataRows(stockId, dividends) {
    const formatedDate = (d) => {
        const date = new Date(d);
        
        const year = date.getFullYear().toString().padStart(4, '0');
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const day = date.getDate().toString().padStart(2, '0');

        return `${year}-${month}-${day}`;
    };

    const tbody = document.getElementById('tableBody');

    for (const dividend of dividends) {
        const recordDate = new Date(dividend['date']);
        const data = {
            record_date: dividend['date'],
            payment_date: formatedDate(recordDate.setMonth(recordDate.getMonth() + 3+1, 0)),
            status: 'paid',
            stock_id: stockId,
            dps: dividend['dividend'],
            saved: false,
        };

        const tr = createRow(data);
        tbody.appendChild(tr);
    }
}


function addPredict() {
    const dividendsOfSelectedStock = getTableData();
    const sorted = dividendsOfSelectedStock.toSorted((a, b) => {
        const asc = true;
        return asc
            ? a['record_date'].localeCompare(b['record_date'])
            : b['record_date'].localeCompare(a['record_date']);
    });

    if (sorted.length == 0) {
        return;
    } 
    const latest = sorted[sorted.length - 1];
    const latestDate =  new Date(latest.record_date);
    const formatedDate = (d) => {
        const date = new Date(d);
        
        const year = date.getFullYear().toString().padStart(4, '0');
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const day = date.getDate().toString().padStart(2, '0');

        return `${year}-${month}-${day}`;
    };
    
    const predict = {
        record_date: formatedDate(latestDate.setMonth(latestDate.getMonth() + 6+1, 0)),
        payment_date: formatedDate(latestDate.setMonth(latestDate.getMonth() + 3+1, 0)),
        status: 'my_prediction',
        stock_id: latest.stock_id,
        dps: latest.dps,
        saved: false,
    };

    const tbody = document.getElementById('tableBody');
    const tr = createRow(predict);
    tbody.appendChild(tr);
}

function addEmptyRow() {
    // const tbody = document.querySelector('#dividendTable tbody');
    const tbody = document.getElementById('tableBody');
    tbody.appendChild(createRow());
}

function createRow(row = {}) {
    const tr = document.createElement('tr');

    if (row.id) {
        tr.dataset.id = row.id;
    }
    
    
    // --- record_date ---
    const tdDate = document.createElement('td');
    const inputDate = document.createElement('input');
    inputDate.type = 'date';
    inputDate.name = 'record_date';
    inputDate.value = row.record_date || '';
    tdDate.appendChild(inputDate);

    // --- status ---
    const tdStatus = document.createElement('td');
    const select = document.createElement('select');
    select.name = 'status';

    // createStatusOptions を要素対応にする
    createStatusOptionsElements(row.status).forEach(option => {
        select.appendChild(option);
    });

    tdStatus.appendChild(select);

    // --- dps ---
    const tdDps = document.createElement('td');
    const inputDps = document.createElement('input');
    inputDps.type = 'number';
    inputDps.step = '0.0001';
    inputDps.name = 'dps';
    inputDps.value = row.dps || '';
    tdDps.appendChild(inputDps);

    // --- payment_date ---
    const tdPayment = document.createElement('td');
    const inputPayment = document.createElement('input');
    inputPayment.type = 'date';
    inputPayment.name = 'payment_date';
    inputPayment.value = row.payment_date || '';
    tdPayment.appendChild(inputPayment);

    // --- delete button ---
    const tdDelete = document.createElement('td');
    const btnDelete = document.createElement('button');
    btnDelete.textContent = '削除';
    btnDelete.addEventListener('click', () => {
        deleteRow(tr);
    });
    tdDelete.appendChild(btnDelete);

    // 
    const tdSaved = document.createElement('td');
    // tdSaved.name = "saved";
    tdSaved.setAttribute('name', 'saved');
    // tr.dataset.saved = (row.saved !== false);
    // tdSaved.textContent = (tr.dataset.saved === "true") ? "保存済": "未保存";
    

    // --- tr に追加 ---
    tr.appendChild(tdDate);
    tr.appendChild(tdStatus);
    tr.appendChild(tdDps);
    tr.appendChild(tdPayment);
    tr.appendChild(tdDelete);
    tr.appendChild(tdSaved);

    setSavedStatus(tr, row.saved);
    

    // ★ 入力変更を監視
    // tr.querySelectorAll('input, select').forEach(el => {
    //     el.addEventListener('input', () => debounceSave(tr));
    //     el.addEventListener('change', () => debounceSave(tr));
    // });

    tr.querySelectorAll('input, select').forEach(el => {
        el.addEventListener('input', () => setSavedStatus(tr, false));
        el.addEventListener('change', () => setSavedStatus(tr, false));
    });

    return tr;
}

function createStatusOptionsElements(selectedValue) {
    const statuses = ['my_prediction', 'expected', 'finalized', 'paid'];
    return statuses.map(status => {
        const option = document.createElement('option');
        option.value = status;
        option.textContent = status;
        if (status === selectedValue) {
            option.selected = true;
        }
        return option;
    });
}

// const saveTimers = new WeakMap();
// function debounceSave(tr) {
//     if (saveTimers.has(tr)) {
//         clearTimeout(saveTimers.get(tr));
//     }

//     const timer = setTimeout(() => {
//         saveRow(tr);
//     }, 500); // 0.5秒待つ

//     saveTimers.set(tr, timer);
// }

function setSavedStatus(tr, saved = false) {
    tr.dataset.saved = saved;
    // if (saved !== null) tr.dataset.saved = saved;
    const td = tr.querySelector('[name="saved"]');

    if (tr.dataset.saved === "true") {
        td.classList.add('saved');
        td.textContent = "保存済";
        
    } else {
        td.classList.remove('saved');
        td.textContent = "未保存";
    } 
}



async function saveRow(tr) {
    const id = tr.dataset.id;

    const data = {
        stock_id: selectedStockId,
        record_date: tr.querySelector('[name="record_date"]').value,
        status: tr.querySelector('[name="status"]').value,
        dps: tr.querySelector('[name="dps"]').value,
        payment_date: tr.querySelector('[name="payment_date"]').value
    };

    // バリデーション
    const messageElement = document.getElementById('message');
    messageElement.textContent = "";

    if (!data.stock_id ||!data.record_date || !data.status || !data.dps || !data.payment_date) {
        console.error("必須項目が未入力のため保存しません");
        return false;
    }

    if (data.payment_date <= data.record_date) {
        messageElement.textContent = "支払日は権利確定日後を設定してください";
        return false;
    }

    let check = false;
    let existsSameDate = false;
    let existsNearDate = false;
    const dividendsOfSelectedStock = getTableData();
    for (const dividend of dividendsOfSelectedStock) {
        if (data.record_date == dividend.record_date) {
            existsSameDate = true;
            break;
        }

        const input = new Date(data.record_date);
        const exists = new Date(dividend.record_date);
        
        if ((input >= exists.setDate(exists.getDate()-45) && input <= exists.setDate(exists.getDate()+45))) {
            existsNearDate = true;
            break;
        }
    }

    // if (existsSameDate) {
    //     messageElement.textContent = "同一の権利確定日は設定できません。";
    //     return false;
    // }
    // if (existsNearDate) {
    //     messageElement.textContent = "3ヶ月以内に権利確定日が設定されている配当があります。";
    // }

    const url = !id ? `${BASE_PATH}/api/dividends/store` : `${BASE_PATH}/api/dividends/update/${id}`;

    console.log("url: ",url);
    console.log("data: ",data);

    try {
        const formData = new FormData();
        formData.append('csrf_token', getCsrfToken());
        formData.append('input_dividends', JSON.stringify(data));

        const res = await fetch(url, {
            method: "POST",
            body: formData,
            credentials: 'same-origin', // セッション / CSRF用
        });

        if (!res.ok) {
            throw new Error('通信エラー');
        }

        const result = await res.json();

        // ★ 新規作成時はIDを付与
        if (!id && result.id) {
            tr.dataset.id = result.id;
        }

        // currentStockIdList = stockView.getUsersStockIdList();
        // alert('登録しました');

        // 保存成功の見た目フィードバック
        tr.style.backgroundColor = '#e6ffed';
        setTimeout(() => tr.style.backgroundColor = '', 300);

        return true;

    } catch (err) {
        console.error(err);
        return false;
    }
}

async function deleteRow(button) {
    const tr = button.closest('tr');
    const id = tr.dataset.id;

    if (id) {
         if (!confirm('削除しますか？')) return;
    }

    // テーブルデータを削除
    tr.remove();

    // データベース側のデータを削除
    if (!id) return;
    const url = `${BASE_PATH}/api/dividends/delete/${id}`;
    try {
        const formData = new FormData();
        formData.append('csrf_token', getCsrfToken());
        // formData.append('input_dividends', JSON.stringify(data));

        const res = await fetch(url, {
            method: "POST",
            body: formData,
            credentials: 'same-origin', // セッション / CSRF用
        });

        if (!res.ok) {
            throw new Error('通信エラー');
        }

        const result = await res.json();
        if (!result.success) throw new Error('削除失敗');

    } catch (err) {
        console.error(err);
        alert('削除に失敗しました');
        return;
    }

    // tr.remove();
}

