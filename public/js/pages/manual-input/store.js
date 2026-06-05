import { BASE_PATH } from '../../config.js';
import { getCsrfToken } from '../../utils/common.js';

const temporaryData = [];
let rawData = [];

export function getInputData() {
    const symbol = document.getElementById('input-symbol').value;
    const account_name = document.getElementById('input-account').value;
    const date = document.getElementById('input-date').value;
    const type = document.getElementById('modal-input-type').value;
    const price = document.getElementById('input-price').value;
    const quantity = document.getElementById('input-quantity').value;
    const subtotal = document.getElementById('input-subtotal').value;
    const content = document.getElementById('input-content').value;

    const temporaryDatum = {
        symbol,
        account_name,
        date,
        type,
        price,
        quantity,
        subtotal,
        content
    }

    const [verifiedRowData, errors] = verifyData(temporaryDatum);
    if (errors.length > 0) {
        alert("入力データにエラーがあります：\n" + errors.join("\n"));
        return;
    }
    console.log("verifiedRowData: ", verifiedRowData);
    console.log("length: ", Object.keys(verifiedRowData).length);

    temporaryData.push(verifiedRowData);
    showTemporaryData();
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

function getStockDataFromTable() {
    const rows = document.querySelectorAll("#data-paste-area table tbody tr");

    const data = Array.from(rows).map(row => {
        const cells = row.querySelectorAll("th, td");

        return {
            date: cells[0].textContent.trim(),
            open: Number(cells[1].textContent.replace(/,/g, "")),
            high: Number(cells[2].textContent.replace(/,/g, "")),
            low: Number(cells[3].textContent.replace(/,/g, "")),
            close: Number(cells[4].textContent.replace(/,/g, "")),
            volume: Number(cells[5].textContent.replace(/,/g, ""))
        };
    });

    return data;
}

export function addToTemporaryData() {
    const rows = document.querySelectorAll("#data-paste-area table tbody tr");

    for (const row of rows) {
        const cells = row.querySelectorAll("th, td");
        const temporaryDatum = {
            date: cells[0].textContent,
            open: cells[1].textContent,
            high: cells[2].textContent,
            low: cells[3].textContent,
            close: cells[4].textContent,
            volume: cells[5].textContent,
        }

        const [verifiedRowData, errors] = verifyData(temporaryDatum);
        if (errors.length === 0) temporaryData.push(verifiedRowData);
    }

    showTemporaryData();
}



function verifyData(row) {
    const verified = {};
    const errors = [];

    // 日付
    if (!(new Date(row.date).getTime())) {
        errors.push("日付が不正です");
    } else {
        verified.date = row.date.trim();
    }

    // 他
    verified.open = row.open ? Number(row.open.replace(/,/g, '')) : 0;
    verified.high = row.high ? Number(row.high.replace(/,/g, '')) : 0;
    verified.low = row.low ? Number(row.low.replace(/,/g, '')) : 0;
    verified.close = row.close ? Number(row.close.replace(/,/g, '')) : 0;
    verified.volume = row.volume ? Number(row.volume.replace(/,/g, '')) : 0;

    return [verified, errors];
}




export async function storeData() {
    if (temporaryData.length === 0) {
        alert("登録するデータがありません");
        return;
    }

    if (!confirm("登録しますか？")) return;

    const url = `${BASE_PATH}/manual-inputs/update-stock-prices`;

    try {
        const formData = new FormData();
        formData.append('csrf_token', getCsrfToken());
        formData.append('stock_id', stock['id']);
        formData.append('prices', JSON.stringify(temporaryData));

        

        

        const res = await fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin', // セッション / CSRF用
        });

        console.log(res);


        if (!res.ok) {
            throw new Error('通信エラー');
        }

        location.reload();

        // const result = await res.json();
        // console.log(result);

        // // currentStockIdList = stockView.getUsersStockIdList();
        // alert('登録しました');

    } catch (err) {
        console.error(err);
    }
}

function showTemporaryData() {
    const dataTable = document.getElementById('temporary-data-table');
    dataTable.innerHTML = "";

    const headerText = ['日付', '始値', '高値', '安値', '終値', '出来高'];
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
        td1.textContent = datum['date'];
        const td2 = document.createElement('td');
        td2.textContent = datum['open'];
        const td3 = document.createElement('td');
        td3.textContent = datum['high'];
        const td4 = document.createElement('td');
        td4.textContent = datum['low'];
        const td5 = document.createElement('td');
        td5.textContent = datum['close'];
        const td6 = document.createElement('td');
        td6.textContent = datum['volume'];

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
        tr.appendChild(td6);
        tr.appendChild(td10);
        dataTable.appendChild(tr);
    }

}



// async function checkData() {
//     const temtativeRegistrated = [];
//     const registered = [];

//     const checkedData = [];
//     for (const row of temporaryData) {
//         // 証券コードをチェック
//         if (row['symbol'] === "") continue;   // 空行はスキップ
//         const symbol = row['symbol'].trim().toUpperCase();
//         // if (!(/^[A-Z0-9]{4}$/.test(symbol))) continue;   // 銘柄コードの形式でないものはスキップ{
//         // const name = row[3].trim();

//         let targetStock = stocks.find(stock => stock.symbol === symbol);
//         if (!targetStock) {
//             const ret = await tentativelyStoreStock(symbol);
//             if (ret) {
//                 targetStock = ret;
//             } else {
//                 continue;
//             }
//         }
//         // if (ret) {
//         //     temtativeRegistrated.push(symbol);
//         // } else {
//         //     registered.push(symbol);
//         // }

//         // 口座をチェック
//         if (row['account_name'] === "") continue;   // 空行はスキップ
//         const accountName = row['account_name'].trim();
//         const targetAccount = accounts.find(account => account.content === accountName);
//         if (!targetAccount) {
//             continue;
//         }

//         // 取引種別をチェック
//         if (row['type'] === "") continue;   // 空行はスキップ
//         const type = row['type'].trim();
//         if (!["1", "2", "3", "0"].includes(type)) continue;   // 取引種別が不正なものはスキップ  

//         // 取引日をチェック
//         if (row['date'] === "") continue;   // 空行はスキップ
//         const date = new Date(row['date']);
//         if (isNaN(date.getTime())) continue;   // 取引日が不正なものはスキップ

//         // 株数をチェック
//         if ((type === "1" || type === "2") && (isNaN(row['quantity']) || row['quantity'] <= 0)) continue;   // 買付・売付で株数が不正なものはスキップ

//         // 株価をチェック
//         if ((type === "1" || type === "2") && (isNaN(row['price']) || row['price'] <= 0)) continue;   // 買付・売付で株価が不正なものはスキップ

//         // 金額をチェック
//         if ((type === "3") && (isNaN(row['subtotal']) || row['subtotal'] <= 0)) continue;   // 配当で金額が不正なものはスキップ

//         // ここまで来たらデータは有効とみなす
//         checkedData.push({
//             symbol,
//             account_id: targetAccount.id,
//             type,
//             date: date.toISOString().split('T')[0],
//             price: row['price'],
//             quantity: row['quantity'],
//             subtotal: row['subtotal'],
//             content: row['content'],
//         });


//     }

//     const unregisteredDiv = document.getElementById('unregistered-symbols');

//     const msg = document.createElement('p');
//     msg.textContent = "以下の銘柄は仮登録されました。後ほど管理者が確認して正式に登録されます。";
//     unregisteredDiv.appendChild(msg);

//     for (const symbol of temtativeRegistrated) {
//         const p = document.createElement('p');
//         p.textContent = symbol;
//         unregisteredDiv.appendChild(p);
//     }
// }

// async function tentativelyStoreStock(symbol) {
//     const url = `${BASE_PATH}/api/stocks/tentative-store`;

//     try {
//         const formData = new FormData();
//         formData.append('csrf_token', getCsrfToken());
//         formData.append('symbol', symbol);
//         // formData.append('symbol', symbol + '.T');
//         formData.append('name', "仮登録");

//         const res = await fetch(url, {
//             method: 'POST',
//             body: formData,
//             credentials: 'same-origin', // セッション / CSRF用
//         });

//         if (!res.ok) {
//             throw new Error('通信エラー');
//         }

//         const result = await res.json();
//         if (result.success) {
//             // console.log("stockId: ", result.data['stockId']);
//             return result.stock;;
//         }

//     } catch (err) {
//         console.error(err);
//     }
//     return null;
// }


// function verifyData(row) {
//     const verifiedData = {};
//     if (row.length < 8) return {};

//     // 日付	年	分類	銘柄	証券コード	口座	株数	株価

//     // 日付
//     if (new Date(row[0]).getTime()) verifiedData['date'] = row[0];

//     // 銘柄コード
//     // if (/^[A-Z0-9]{4}$/.test(row[4].toUpperCase())) {
//         verifiedData['symbol'] = row[4].toUpperCase();
//     // }

//     if (row[5] !== "") verifiedData['account_name'] = row[5].trim();


//     // if (row[2] === "買付" || row[2] === "売付") verifiedData['type_name'] = row[2];
//     if (row[2] === "買付" || row[2] === "売付" || row[2] === "配当") verifiedData['type_name'] = row[2];


//     // if (!isNaN(row[6]) && row[6].trim() !== "") verifiedData['quantity'] = Number(row[6]);
//     if (!isNaN(row[6].replace(/,/g, '')) && row[6].trim() !== "") {
//         verifiedData['quantity'] = Number(row[6].replace(/,/g, ''));
//     } else {
//         verifiedData['quantity'] = 0;
//     }


//     if (!isNaN(row[7].replace(/,/g, '')) && row[7].trim() !== "") {
//         verifiedData['price'] = Number(row[7].replace(/,/g, ''));
//     } else {
//         verifiedData['price'] = 0;
//     }


//     if (!isNaN(row[8].replace(/,/g, '')) && row[8].trim() !== "") verifiedData['subtotal'] = Number(row[8].replace(/,/g, ''));



//     return verifiedData;

// }
