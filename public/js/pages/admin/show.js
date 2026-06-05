import { BASE_PATH } from '../../config.js';
import { sortTrades } from './common-function.js';


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
    const container = document.getElementById('searched-stock-list');
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
        { label: '状態', key: 'state' ,sticky: true},
        { label: '銘柄名', key: 'name' ,sticky: true},
        { label: '証券コード', key: 'symbol' },
        { label: '日付', key: 'date' },
        { label: '株価', key: 'latest_close' },
        { label: '操作', key: 'operation' },
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
            {element: 'text', value: trade.tentative===1?'仮':'', sticky: true},
            {element: 'text', value: trade.name, sticky: true, link: trade.id},
            {element: 'text', value: trade.symbol},
            {element: 'text', value: trade.date},
            {element: 'text', value: trade.price, alignment: 'right'},
            {element: 'button', value: trade.id},
        ];

        values.forEach(v => {
            const td = document.createElement('td');
            if (v.sticky) {
                td.classList.add("sticky");
            }
            switch (v.element) {
                case 'text':
                    td.textContent = v.value;
                    if (v.alignment === 'right') {
                        td.classList.add("right-alignment");
                    }   
                    if (v.link) {
                        td.addEventListener('click', ()=> {
                            const redirectUri = encodeURI(`${BASE_PATH}/admins`);
                            location.href = `${BASE_PATH}/stocks/show-detail/${v.link}?redirect=${redirectUri}`
                        });
                        td.classList.add("link");
                    }             
                    break;
                case 'button':
                    const button = document.createElement('button');
                    button.textContent = '操作';
                    button.addEventListener('click', () => {
                        console.log('click', v.value);
                        document.dispatchEvent(new CustomEvent('edit-stock', {
                            detail: { stockId: v.value }
                        }))
                    });
                    td.appendChild(button);
                    break;
                default:
                    break;
            }
            // td.textContent = v.value;
            // if (v.option) {
            //     if (v.option['alignment'] === 'right') {
            //         td.classList.add("right-alignment");
            //     }
            //     if (v.option['sticky'] === true) {
            //         td.classList.add("sticky");
            //     }
            //     if (v.option['link']) {
             
            //         td.addEventListener('click', () => {
            //             const redirectUri = encodeURI(`${BASE_PATH}/`);
            //             location.href = `${BASE_PATH}/stocks/show-detail/${v.option.link}?redirect=${redirectUri}`
            //         });
            //         td.classList.add("link");
            //     }
            //     if (v.option['button']) {
            //         td.addEventListener('click', () => {
            //             const redirectUri = encodeURI(`${BASE_PATH}/`);
            //             location.href = `${BASE_PATH}/stocks/show-detail/${v.option.link}?redirect=${redirectUri}`
            //         });
            //         td.classList.add("link");
            //     }
            // }
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
