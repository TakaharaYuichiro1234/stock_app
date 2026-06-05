import { BASE_PATH } from '../../config.js';
import { MenuItem } from '../../utils/menu-item.js';
import { Menu } from '../../utils/menu.js';
import { getSelectedItems } from './common-function.js';
import { fetchAssets } from './fetch.js';
import * as drawChartModule from './draw-chart.js';
import * as partsModule from './parts.js';
import * as showModule from './show.js';
import * as showSummaryModule from './show-summary.js';


// ===========================================================
// init
// ===========================================================
document.addEventListener("DOMContentLoaded", async () => {
    init();
});

async function init() {
    initMenu();
    initAddButtonIntoHeader();
    await initFilter();

    partsModule.showVariousPanels();


    await refreshData();
    setFilterPosition();

    window.addEventListener("resize", ()=>{
        setFilterPosition();
    });

    document.getElementById('summary-date').addEventListener('change', (event) =>  {
        showSummaryModule.showSummary();
    });

    document.getElementById('asset-type-select').addEventListener('change', (event) =>  {
        drawChartModule.drawAssetPieChart();
    });

}

function setFilterPosition() {
    const elementsPairs = [['filterBtn','filterBox'], ['accountFilterBtn','accountFilterBox']];
    for (const elements of elementsPairs) {
        const btn = document.getElementById(elements[0]);
        const rect = btn.getBoundingClientRect();
        const absoluteTop = rect.top + window.scrollY;
        const absoluteLeft = rect.left + window.scrollX;

        const box = document.getElementById(elements[1]);
        // box.style.top =  '70px';
        // box.style.top = String(absoluteTop + 50) + 'px';
        box.style.left = String(absoluteLeft) + 'px';
    }
}
function initAddButtonIntoHeader() {
    const btn1 = document.createElement('button');
    btn1.textContent = "銘柄";
    btn1.id = "filterBtn";

    const btn2 = document.createElement('button');
    btn2.textContent = "口座";
    btn2.id = "accountFilterBtn";

    const container = document.getElementById('header-option');
    container.appendChild(btn1);
    container.appendChild(btn2);
}

async function refreshData() {
    const [selectedStockIds, selectedAccountIds] = getSelectedItems();
    const [dailyAssetsTotal, latestAssetDetail, expectedDividends] = await fetchAssets(selectedStockIds, selectedAccountIds);

    const totalizedData = totalizeByAccount(latestAssetDetail, expectedDividends);
    // const totalizedData = totalizeTradeDataByAccount(latestAssetDetail);
    // const totalizedExpectedDividends = totalizeExpectedDividendsByAccount(expectedDividends);

    const tableData = restructureTradeDataIntoTable(totalizedData);
    showModule.setTableData(tableData);
    showModule.showTable();


    showSummaryModule.setSummaryData(dailyAssetsTotal);
    showSummaryModule.showSummary();
    

    drawChartModule.setChartData(dailyAssetsTotal, totalizedData);
    drawChartModule.drawChart();
}



// 全ての口座を合計した集計結果を返す
function createSummary() {
    return {
        total_quantity: 0,
        perchasedValue: 0,
        total_realize: 0,
        total_dividend: 0,
        expected_dividend: 0,
    };
}

function totalizeByAccount(latestAssetDetail, expectedDividends) {
    const resultMap = new Map();

    for (const row of latestAssetDetail) {
        if (!resultMap.has(row.stock_id)) {
            resultMap.set(row.stock_id, createSummary());
        }

        const item = resultMap.get(row.stock_id);

        item.total_quantity += row.total_quantity;
        item.perchasedValue += row.total_quantity * row.average_price;
        item.total_realize += row.total_realize;
        item.total_dividend += row.total_dividend;
    }

    for (const row of expectedDividends) {
        if (!resultMap.has(row.stock_id)) {
            resultMap.set(row.stock_id, createSummary());
        }

        resultMap.get(row.stock_id).expected_dividend += row.expected_dividend;
    }

    return resultMap;
}



// function totalizeTradeDataByAccount(latestAssetDetail) {
//     const resultMap = new Map();

//     for (const row of latestAssetDetail) {
//         const value = row['total_quantity'] * row['average_price'];

//         if (!resultMap.has(row['stock_id'])) {
//             resultMap.set(row['stock_id'], {
//                 'total_quantity': 0,
//                 'perchasedValue': 0,
//                 'total_realize': 0,
//                 'total_dividend': 0,
//                 'expected_dividend': 0,

//             });
//         }

//         resultMap.set(
//             row['stock_id'],
//             {
//                 'total_quantity': resultMap.get(row['stock_id'])['total_quantity'] + row['total_quantity'],
//                 'perchasedValue': resultMap.get(row['stock_id'])['perchasedValue'] + value,
//                 'total_realize': resultMap.get(row['stock_id'])['total_realize'] + row['total_realize'],
//                 'total_dividend': resultMap.get(row['stock_id'])['total_dividend'] + row['total_dividend'],

//             }
//         );
//     }
//     return resultMap;
// }

// function totalizeExpectedDividendsByAccount(expectedDividends) {
//     const resultMap = new Map();

//     for (const row of expectedDividends) {
//         if (!resultMap.has(row['stock_id'])) {
//             resultMap.set(row['stock_id'], {
//                 'expected_dividend': 0,
//             });
//         }

//         resultMap.set(
//             row['stock_id'],
//             {
//                 'expected_dividend': resultMap.get(row['stock_id'])['expected_dividend'] + row['expected_dividend'],
//             }
//         );
//     }
//     return resultMap;
// }

function restructureTradeDataIntoTable(resultMap) {
    const result = [];
    for (const [stockId, obj] of resultMap.entries()) {
        if (obj['total_quantity'] <= 0 && obj['total_realize']==0 && obj['total_dividend']<=0) continue;
        const targetStock = stocks.find(stock => String(stock.id) === String(stockId));
        if (!targetStock) continue;
        const evaluatedValue = (targetStock?.latest_close ?? 0) * obj['total_quantity'];
        // const expected_dividend = expected_dividends.find(ed => String(ed.stock_id) === String(stockId))?.expected_dividend * obj['total_quantity'] ?? 0;
        const expected_dividend = obj['expected_dividend'];
        result.push({
            stock_id: stockId,
            name: targetStock?.name ?? '',
            symbol: targetStock?.symbol ?? '',
            latest_date: targetStock?.latest_date ?? '',
            latest_close: Number(targetStock.latest_close),
            prev_date: targetStock?.prev_date ?? '',
            prev_close: Number(targetStock.prev_close),
            percent_diff: Number(targetStock.percent_diff),
            digit: Number(targetStock.digit),
            tentative: targetStock?.tentative ?? 0,
            total_quantity: obj['total_quantity'],
            average_price: obj['total_quantity']!=0 ? obj['perchasedValue'] / obj['total_quantity']: "---",
            perchasedValue: obj['perchasedValue'],
            evaluatedValue: evaluatedValue,
            profitAndLoss: evaluatedValue - obj['perchasedValue'],
            
            total_realize: obj['total_realize'],
            total_dividend: obj['total_dividend'],
            total_profit: evaluatedValue - obj['perchasedValue'] + obj['total_realize'] + obj['total_dividend'],
            expected_dividend: expected_dividend,
        });
    }

    return result;
}



// ===========================================================
// フィルター機能
// ===========================================================
function openFilter(filterBox) {
    filterBox.style.display = "block";
}
let isChanged = false;
async function closeFilter(filterBox) {
    if (isChanged) {
        await refreshData();
    }
    filterBox.style.display = "none";
    isChanged = false;
}

async function initFilter() {
    await createFilter("filterBtn", "filterBox", "filterList", getStocks);
    await createFilter("accountFilterBtn", "accountFilterBox", "accountFilterList", getAccounts);
}

async function createFilter(btnId, boxId, listId, getAction) {
    const filterBtn = document.getElementById(btnId);
    const filterBox = document.getElementById(boxId);

    document.addEventListener("click", (e) => {
        const isClickInside = filterBox.contains(e.target) || filterBtn.contains(e.target);
        if (!isClickInside && filterBox.style.display === "block") {
            closeFilter(filterBox);
        }
    });

    // フィルター表示/非表示
    filterBtn.addEventListener("click", () => {
        if (filterBox.style.display === "block") {
            closeFilter(filterBox);
        } else {
            openFilter(filterBox);
        }
    });

    const data = await getAction();
    const filterList = document.getElementById(listId);

    // 「全て」チェック
    const checkAllLabel = document.createElement("label");
    const checkAll = document.createElement("input");
    checkAll.type = "checkbox";
    checkAll.id = "checkAll";
    checkAll.checked = true;

    checkAllLabel.appendChild(checkAll);
    checkAllLabel.append(" 全て");

    filterList.appendChild(checkAllLabel);
    filterList.appendChild(document.createElement("hr"));

    // 各項目を生成
    const itemCheckboxes = [];

    data.forEach(item => {
        const label = document.createElement("label");
        const checkbox = document.createElement("input");

        checkbox.type = "checkbox";
        checkbox.value = item.id;
        checkbox.checked = true;
        checkbox.classList.add("item");

        label.appendChild(checkbox);
        label.append(` ${item.name}`);

        filterList.appendChild(label);
        filterList.appendChild(document.createElement("br"));

        itemCheckboxes.push(checkbox);
    });

    // 「全て」クリック時
    checkAll.addEventListener("change", () => {
        itemCheckboxes.forEach(cb => {
            cb.checked = checkAll.checked;
        });
        isChanged = true;
    });

    // 個別チェック時
    itemCheckboxes.forEach(cb => {
        cb.addEventListener("change", () => {
            checkAll.checked = itemCheckboxes.every(c => c.checked);
            isChanged = true;
        });
    });

    isChanged = false;
}

async function getStocks() {
    // const response = await fetch(`${BASE_PATH}/api/stocks/get-all`, {
    //     method: 'GET',
    //     headers: {
    //         'Content-Type': 'application/json'
    //     }
    // });

    // if (!response.ok) {
    //     console.error('検索に失敗しました');
    //     return;
    // }

    // const json = await response.json();
    // // console.log(json);
    // return json.stock.map(stock => {
    //     return { 'id': stock.id, 'name': stock.name, 'symbol': stock.symbol };
    // });

    return stocks.map(stock => {
        return { 'id': stock.id, 'name': stock.name, 'symbol': stock.symbol };
    });

}

async function getAccounts() {
    const response = await fetch(`${BASE_PATH}/api/accounts/`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    });

    if (!response.ok) {
        console.error('検索に失敗しました');
        return;
    }

    const json = await response.json();
    // console.log(json);
    return json.account.map(a => {
        return { 'id': a.id, 'name': a.content };
    });
}


// ===========================================================
// メニュー
// ===========================================================
function initMenu() {
    const items = [];

    if (isAdmin) {
        items.push(
            new MenuItem({
                caption: '🛡️登録銘柄管理',
                name: 'admin',
                action: () => location.href = `${BASE_PATH}/admins`
            }),
            new MenuItem({
                caption: '🛡️予想配当管理',
                name: 'magane-dividend',
                action: () => location.href = `${BASE_PATH}/dividends`
            }),
        );
    }

    if (user) {
        items.push(
            new MenuItem({
                caption: '取引データ入力',
                name: 'input-trade',
                action: () => location.href = `${BASE_PATH}/trades`
            }),
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