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
    const clipboardData = await navigator.clipboard.readText();
    rawData = parseExcelClipboard(clipboardData);

    const dataTable = document.getElementById('data-paste-table');
    for (const row of rawData) {
        const tr = document.createElement('tr');
        for (const cell of row) {
            const td = document.createElement('td');
            td.textContent = cell;
            tr.appendChild(td);
        }
        dataTable.appendChild(tr);
    }
}

export function addToTemporaryData() {
    const typeText = {
        1: "買付",
        2: "売付",
        3: "配当",
        0: "メモ",
    };

    for (const row of rawData) {
        // 0:日付 1:年 2:分類 3:銘柄 4:証券コード 5:口座 6:株数 7:株価 8:金額 9:買付時価格 10:実現損益/配当/税金 11:損益率 12:備考
        const symbol = row[4];
        const account_name = row[5];
        const date = row[0];
        const type = Object.entries(typeText).find(
            ([key, value]) => value === row[2]
        )?.[0] || "0";
        const price = row[7];
        const quantity = row[6];
        const subtotal = row[8];
        const content = row[12];

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
        console.log("verifiedRowData: ", verifiedRowData);
        console.log("length: ", Object.keys(verifiedRowData).length);

        if (errors.length > 0) {
            console.error("入力データにエラーがあります：\n" + errors.join("\n"));
            continue;
        }

        temporaryData.push(verifiedRowData);
    }
    showTemporaryData();
}

export async function storeData() {
    if (temporaryData.length === 0) {
        alert("登録するデータがありません");
        return;
    }

    if (!confirm("登録しますか？")) return;

    const data = JSON.stringify(temporaryData);
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

function showTemporaryData() {
    const dataTable = document.getElementById('temporary-data-table');
    dataTable.innerHTML = "";

    const headerText = ['取引日', '取引種別', '銘柄コード', '銘柄名', '口座', '株数', '株価', '金額', 'メモ', '操作'];
    const headerRow = document.createElement('tr');
    for (const text of headerText) {
        const th = document.createElement('th');
        th.textContent = text;
        th.classList.add('sticky');
        headerRow.appendChild(th);
    }
    dataTable.appendChild(headerRow);

    let unregisteredSymbols = [];
    let unregisteredAccounts = [];
    for (const trade of temporaryData) {
        console.log("trade: ", trade);
        const typeText = {
            1: "買付",
            2: "売付",
            3: "配当",
            0: "メモ",
        };

        const stock = stocks.find(stock => stock.symbol === trade['symbol']);
        if (!stock) {
            unregisteredSymbols.push(trade['symbol']);
        }

        const account = accounts.find(account => account.content === trade['account_name']);
        if (!account) {
            unregisteredAccounts.push(trade['account_name']);
        }

        const td1 = document.createElement('td');
        td1.textContent = trade['date'];
        const td2 = document.createElement('td');
        td2.textContent = typeText[trade['type']];
        const td3 = document.createElement('td');
        td3.textContent = trade['symbol'];
        td3.style.color = stock ? "white" : "red";
        const td4 = document.createElement('td');
        td4.textContent = stock ? stock.name : "未登録";;
        td4.style.color = stock ? "white" : "red";
        const td5 = document.createElement('td');
        td5.textContent = trade['account_name'];
        td5.style.color = account ? "white" : "red";
        const td6 = document.createElement('td');
        td6.textContent = (trade['type'] === '1' || trade['type'] === '2') ? trade['quantity'] : "---";
        const td7 = document.createElement('td');
        td7.textContent = (trade['type'] === '1' || trade['type'] === '2') ? trade['price'] : "---";
        const td8 = document.createElement('td');
        td8.textContent = (trade['type'] === '1' || trade['type'] === '2') ? trade['quantity'] * trade['price'] :
            (trade['type'] === '3') ? trade['subtotal'] : "---";

        const isRegistered = stocks.some(stock => stock.symbol === trade['symbol']);
        const td9 = document.createElement('td');
        td9.textContent = trade['content'];

        const button = document.createElement('button');
        button.textContent = "削除";
        button.classList.add('operation-button');
        button.addEventListener('click', () => {
            const index = temporaryData.indexOf(trade);
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
        tr.appendChild(td7);
        tr.appendChild(td8);
        tr.appendChild(td9);
        tr.appendChild(td10);
        dataTable.appendChild(tr);
    }

    const unregisteredDiv = document.getElementById('unregistered-symbols');
    unregisteredDiv.innerHTML = "";

    unregisteredSymbols = [...new Set(unregisteredSymbols)];
    unregisteredAccounts = [...new Set(unregisteredAccounts)];

    if (unregisteredSymbols.length > 0) {
        const msg = document.createElement('p');
        msg.textContent = "以下の銘柄は未登録です。後ほど管理者が確認して正式に登録されます。";
        unregisteredDiv.appendChild(msg);

        for (const symbol of unregisteredSymbols) {
            const p = document.createElement('p');
            p.textContent = symbol;
            unregisteredDiv.appendChild(p);
        }
    }

    if (unregisteredAccounts.length > 0) {
        const msg = document.createElement('p');
        msg.textContent = "以下の口座は未登録です。新規の口座として登録されます。";
        unregisteredDiv.appendChild(msg);

        for (const account of unregisteredAccounts) {
            const p = document.createElement('p');
            p.textContent = account;
            unregisteredDiv.appendChild(p);
        }
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



function verifyData(row) {
    const verified = {};
    const errors = [];

    // 銘柄コード
    if (row.symbol === "") {
        errors.push("銘柄コードが入力されていません");
    } else {
        verified.symbol = row.symbol.trim().toUpperCase();
    }

    // 口座
    if (row.account_name === "") {
        errors.push("口座が入力されていません");
    } else {
        verified.account_name = row.account_name.trim();
    }

    // 日付
    if (!(new Date(row.date).getTime())) {
        errors.push("取引日が不正です");
    } else {
        verified.date = row.date.trim();
    }

    // 取引種別
    if (!(row.type === '1' || row.type === '2' || row.type === '3' || row.type === '0')) {
        errors.push("取引種別が不正です");
    } else {
        verified.type = row.type;
    }

    // 株数
    verified.price = row.price ? Number(row.price.replace(/,/g, '')) : 0;

    // 株価
    verified.quantity = row.quantity ? Number(row.quantity.replace(/,/g, '')) : 0;

    // 金額
    verified.subtotal = row.subtotal ? Number(row.subtotal.replace(/,/g, '')) : 0;

    // メモ
    if (!row.content) {
        verified.content = "";
    } else {
        verified.content = row.content.trim();
    }
    if (verified.content.length > 1000) {
        verified.content = verified.content.substring(0, 1000);
    }

    return [verified, errors];
}

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
