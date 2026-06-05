import { BASE_PATH } from '../../config.js';
import { sortTrades } from '../dashboard/common-function.js';


// ===========================================================
// 銘柄ごとの一覧表表示
// ===========================================================
let currentSort = {
    key: 'profitAndLoss', // 初期ソート
    asc: false
};
let tableData = [];

export function setTableData(tableData0) {
    tableData = tableData0;
}

export function showTable() {
    const container = document.getElementById('overall-container');
    if (tableData.length === 0) {
        container.innerHTML = '<p>取引データがありません。</p>';
        return;
    }

    sortTrades(tableData, currentSort.key, currentSort.asc);

    const table = document.createElement('table');

    // ヘッダー行
    const headerThead = document.createElement('thead');
    const headerRow = document.createElement('tr');
    const headers = [
        
        { label: '銘柄名', key: 'name' ,sticky: true},
        { label: '証券コード', key: 'symbol' },
        { label: '数量', key: 'total_quantity' },

        { label: '株価', key: 'latest_close' },

        { label: '平均取得価格', key: 'average_price' },
        { label: '取得金額', key: 'perchasedValue' },
        { label: '評価額', key: 'evaluatedValue' },
        { label: '評価損益', key: 'profitAndLoss' },
        { label: '実現損益累計', key: 'total_realize' },
        { label: '配当累計', key: 'total_dividend' },
        { label: '損益合計', key: 'total_profit' },
        { label: '見込配当', key: 'expected_dividend' },

    ];


    headers.forEach(header => {
        const th = document.createElement('th');
        // th.textContent = header.label;
        th.textContent = header.label + (
            currentSort.key === header.key
                ? (currentSort.asc ? ' ▲' : ' ▼')
                : ''
        );

        th.style.cursor = 'pointer';
        th.classList.add("w50");

        th.addEventListener('click', () => {
            if (currentSort.key === header.key) {
                currentSort.asc = !currentSort.asc; // 同じ列なら反転
            } else {
                currentSort.key = header.key;
                currentSort.asc = true; // 列変更時は昇順スタート
            }

            showTable(); // 再描画
        });

        if (header.sticky) {
            th.classList.add("sticky-corner");
        }

        headerRow.appendChild(th);
    });

    headerThead.appendChild(headerRow);

    table.appendChild(headerThead);

    // データ行
    const tbody = document.createElement('tbody');
    for (const trade of tableData) {
        const row = document.createElement('tr');
        const values = [
            
            {value: trade.name, option:{sticky: true, link: trade.stock_id}},
            {value: trade.symbol},
            {value: trade.total_quantity, option: {alignment: 'right'}},
            {value: trade.latest_close.toLocaleString('ja-JP', { minimumFractionDigits: trade.digit, maximumFractionDigits: trade.digit }), option: {alignment: 'right'}},
            {value: trade.average_price.toLocaleString('ja-JP', { minimumFractionDigits: trade.digit, maximumFractionDigits: trade.digit }), option: {alignment: 'right'}},
            {value: Math.floor(trade.perchasedValue).toLocaleString(), option: {alignment: 'right'}},
            {value: trade.tentative === 1 ? '---' : Math.floor(trade.evaluatedValue).toLocaleString(), option: {alignment: 'right'}},
            {value: trade.tentative === 1 ? '---' : Math.floor(trade.profitAndLoss).toLocaleString(), option: {alignment: 'right'}},
            {value: trade.tentative === 1 ? '---' : Math.floor(trade.total_realize).toLocaleString(), option: {alignment: 'right'}},
            {value: trade.tentative === 1 ? '---' : Math.floor(trade.total_dividend).toLocaleString(), option: {alignment: 'right'}},
            {value: trade.tentative === 1 ? '---' : Math.floor(trade.total_profit).toLocaleString(), option: {alignment: 'right'}},
            {value: trade.tentative === 1 ? '---' : Number(trade.expected_dividend).toLocaleString(), option: {alignment: 'right'}},
        ];

        values.forEach(v => {
            const td = document.createElement('td');
            td.textContent = v.value;
            if (v.option) {
                if (v.option['alignment'] === 'right') {
                    td.classList.add("right-alignment");
                }
                if (v.option['sticky'] === true) {
                    td.classList.add("sticky");
                }
                if (v.option['link']) {
             
                    td.addEventListener('click', () => {
                        const redirectUri = encodeURI(`${BASE_PATH}/`);
                        location.href = `${BASE_PATH}/stocks/show-detail/${v.option.link}?redirect=${redirectUri}`
                    });
                    td.classList.add("link");
                    // td.addEventListener("mouseenter", (event) => {
                    //         event.target.style.color = "purple";
                    //         setTimeout(() => {
                    //             event.target.style.color = "";
                    //             }, 500);
                    //         },
                    //         false,
                    //     );
                }
            }
            td.classList.add("w10");
            row.appendChild(td);
        });

        tbody.appendChild(row);

        table.appendChild(tbody);
    }

    // コンテナに追加
    container.innerHTML = '';
    container.appendChild(table);
}
