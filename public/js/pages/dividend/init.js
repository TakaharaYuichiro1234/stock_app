import { BASE_PATH } from '../../config.js';
import { MenuItem } from '../../utils/menu-item.js';
import { Menu } from '../../utils/menu.js';
import { getCsrfToken } from '../../utils/common.js';
import * as inputModule from './input.js';
import * as batchInputModule from './batch-input.js';

document.addEventListener("DOMContentLoaded", () => {
    init();
});

async function init() {
    initMenu();
    showAllData();

    inputModule.setListener();
    inputModule.initView();

    batchInputModule.initModal();
    batchInputModule.initRegistrationEvents();


}


function showAllData() {
    const dataTable = document.getElementById('all-data-table-body');
    dataTable.innerHTML = "";

    for (const dividend of dividends) {
        const stock = stocks.find(s => s.id === dividend.stock_id);
        if (!stock) continue;

        const data = [
            stock.name,
            stock.symbol,
            dividend.record_date,
            dividend.status,
            dividend.dps,
            dividend.payment_date
        ];

        const tds = data.map(text => {
            const td = document.createElement('td');
            td.textContent = text;
            return td;
        }); 

        const tr = document.createElement('tr');
        tds.forEach(td => tr.appendChild(td));
        dataTable.appendChild(tr);         
    }
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
