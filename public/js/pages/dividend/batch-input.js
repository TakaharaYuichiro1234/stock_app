import { BASE_PATH } from '../../config.js';
import { getCsrfToken } from '../../utils/common.js';

const temporaryData = [];
let rawData = [];


export function initModal() {
    // モーダル画面表示・非表示
    document.getElementById("batch-input-button").addEventListener("click", () => {
        document.querySelector(".modal").classList.remove("hidden");
    });

    document.querySelector(".modal-close").addEventListener("click", () => {
        document.querySelector(".modal").classList.add("hidden");
    });

    document.getElementById("add-to-temporary-data").addEventListener("click", () => {
        batchInputModule.addToTemporaryData();
        document.querySelector(".modal").classList.add("hidden");
    });
}

export function initRegistrationEvents() {
    // 「貼り付け」クリック時の処理
    document.getElementById('paste-from-clipboard').addEventListener('click', async () => {
        await batchInputModule.pasteFromClipboard();
    });

    document.getElementById('store-button').addEventListener('click', async () => {
        await batchInputModule.storeData();
    });
}

export async function pasteFromClipboard() {
  try {
    // 1. パーミッションの確認
    const permission = await navigator.permissions.query({ name: 'clipboard-read' });
    if (permission.state === 'denied') {
      throw new Error('クリップボードへのアクセスが拒否されています。');
    }

    // 2. クリップボードからデータを読み込む
    const clipboardItems = await navigator.clipboard.read();

    for (const clipboardItem of clipboardItems) {
      // 3. HTML形式のデータが含まれているかチェック
      if (clipboardItem.types.includes('text/html')) {
        const blob = await clipboardItem.getType('text/html');
        const htmlString = await blob.text();

        // 4. 表コンテナに貼り付ける
        const container = document.getElementById('data-paste-area');
        container.innerHTML = htmlString;
        return;
      }
    }
    
    // HTML形式がない場合のフォールバック（CSVやプレーンテキスト）
    if (clipboardItems[0] && clipboardItems[0].types.includes('text/plain')) {
       const textBlob = await clipboardItems[0].getType('text/plain');
       const textString = await textBlob.text();
       console.log('テキストとして取得: ', textString);
    }

  } catch (error) {
    console.error('ペーストに失敗しました:', error);
  }
}

export function addToTemporaryData() {
    const rows = document.querySelectorAll("#data-paste-area table tbody tr");

    for (const row of rows) {
        const cells = row.querySelectorAll("th, td");
        const temporaryDatum = {
            symbol: cells[0].textContent,
            record_date: cells[3].textContent,
            status_name: cells[6].textContent,
            dps: cells[5].textContent,
            payment_date: cells[4].textContent,
        }

        const [verifiedRowData, errors] = verifyData(temporaryDatum);
        if (errors.length === 0) temporaryData.push(verifiedRowData);
    }

    console.log(temporaryData); 
    showTemporaryData();
}

function verifyData(row) {
    const verified = {};
    const errors = [];

    // 証券コード
    const targetStock = stocks.find(s => s.symbol === row.symbol.trim());
    if (!targetStock) {
        errors.push("登録されていない証券コードです");
    } else {
        verified.symbol = row.symbol.trim();
        verified.stock_id = targetStock.id;
        verified.name = targetStock.name;
    }

    // 日付
    if (!(new Date(row.record_date.trim()).getTime())) {
        errors.push("権利確定日が不正です");
    } else {
        verified.record_date = row.record_date.trim();
    }

    if (!(new Date(row.payment_date.trim()).getTime())) {
        errors.push("支払日が不正です");
    } else {
        verified.payment_date = row.payment_date.trim();
    }

    // status
    const statuses = {'独自予測':'my_prediction', '予想':'expected', '確定':'finalized', '済':'paid'};
    const keys = Object.keys(statuses);

    if (!keys.includes(row.status_name.trim())) {
        errors.push("statusが不正です");
    } else {
        verified.status = statuses[row.status_name.trim()];
        verified.status_name = row.status_name.trim();
    }

    // 他
    verified.dps = row.dps ? Number(row.dps.replace(/,/g, '')) : 0;

    return [verified, errors];
}

export async function storeData() {
    if (temporaryData.length === 0) {
        alert("登録するデータがありません");
        return;
    }

    if (!confirm("登録しますか？")) return;

    const url = `${BASE_PATH}/api/dividends/upsert`;
    try {
        const formData = new FormData();
        formData.append('csrf_token', getCsrfToken());
        formData.append('input_dividends_list', JSON.stringify(temporaryData));

        const res = await fetch(url, {
            method: "POST",
            body: formData,
            credentials: 'same-origin', // セッション / CSRF用
        });

        console.log(res);

        if (!res.ok) {
            throw new Error('通信エラー');
        }

        const result = await res.json();
        console.log(result);

        location.reload();
    } catch (err) {
        console.error(err);
    }
}

function showTemporaryData() {
    const dataTable = document.getElementById('temporary-data-table');
    dataTable.innerHTML = "";

    const headerText = ['証券コード', '権利確定日', 'status', 'dps', '支払(予想)日'];
    const headerRow = document.createElement('tr');
    for (const text of headerText) {
        const th = document.createElement('th');
        th.textContent = text;
        th.classList.add('sticky');
        headerRow.appendChild(th);
    }
    dataTable.appendChild(headerRow);

    for (const datum of temporaryData) {
        const td1 = document.createElement('td');
        td1.textContent = datum['symbol'];
        const td2 = document.createElement('td');
        td2.textContent = datum['record_date'];
        const td3 = document.createElement('td');
        td3.textContent = datum['status'];
        const td4 = document.createElement('td');
        td4.textContent = datum['dps'];
        const td5 = document.createElement('td');
        td5.textContent = datum['payment_date'];

        const button = document.createElement('button');
        button.textContent = "削除";
        button.classList.add('operation-button');
        button.addEventListener('click', () => {
            const index = temporaryData.indexOf(datum);
            if (index > -1) {
                temporaryData.splice(index, 1);
                showTemporaryData();
            }
        });
        const td10 = document.createElement('td');
        td10.appendChild(button);

        const tr = document.createElement('tr');
        tr.appendChild(td1);
        tr.appendChild(td2);
        tr.appendChild(td3);
        tr.appendChild(td4);
        tr.appendChild(td5);
        tr.appendChild(td10);
        dataTable.appendChild(tr);
    }

}

