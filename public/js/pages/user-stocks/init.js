let stockView;

function init() {
    initMenu();
    initViewSwitch();

    stockView = new StocksViewModule();
    initEventsFromStockView();
    initSearchedSection();
    initUsersSection();   
}

function initMenu() {
    const items = [];

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

function initViewSwitch() {
    const viewSwitch = (isSearch) => {
        document.getElementById("view-switch-searched").classList.toggle("unselected", !isSearch);
        document.getElementById("view-switch-users").classList.toggle("unselected", isSearch);

        document.getElementById("searched").classList.toggle("hidden-when-mobile", !isSearch);
        document.getElementById("users").classList.toggle("hidden-when-mobile", isSearch);
    }
            
    viewSwitch(true);

    document.getElementById("view-switch-searched").addEventListener("click", function () {
        viewSwitch(true);
    });
    document.getElementById("view-switch-users").addEventListener("click", function () {
        viewSwitch(false);
    });
}

// stockViewModuleからのイベントを受ける
function initEventsFromStockView() {
    document.addEventListener("show-detail", (e) => {
        const { stockId } = e.detail;
        const redirectUri = encodeURI(`${BASE_PATH}/user-stocks`);
        location.href=`${BASE_PATH}/stocks/show/${stockId}?redirect=${redirectUri}`
    });
}

async function initSearchedSection() {
    await refreshSearchedStocks("");

    document.getElementById('search-submit-button').addEventListener('click', async () => {
        const input = document.getElementById('search-input').value.trim();
        await refreshSearchedStocks(input);
    });
}

async function refreshSearchedStocks(keywordInputs) {
    const params = new URLSearchParams({
        keywords: keywordInputs
    });

    const res = await fetch(`${BASE_PATH}/api/stocks/get-filtered?${params.toString()}`, {
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
    const stocks = json.data;

    const searchedStocks = [];
    for (const stock of stocks) {
        const s = {
            id: stock['id'],
            name: stock['name'],
            symbol: stock['symbol']
        }
        searchedStocks.push(s);
    }

    const listDomId = "searched-stock-list";
    stockView.initFirstStockView(searchedStocks, listDomId);
}

async function initUsersSection() {
    setUserOperationButtonsListener();
    const usersStocks = await setUsersStocks();
    stockView.initSecondStockView(usersStocks, "users-stock-list");
}

async function setUsersStocks() {
    const usersStocks = [];
    try {
        const res = await fetch(`${BASE_PATH}/api/stocks/get-user-stocks`);
        if (!res.ok) throw new Error(`APIエラー: ${res.status}`);
        const json = await res.json(); 
        const stocks = json.data;
        for (const stock of stocks) {
            const s = {
                id: stock['id'],
                name: stock['name'],
                symbol: stock['symbol']
            }
            usersStocks.push(s);
        }
    } catch (err) {
        console.error(err);
    }

    return usersStocks;
}

function setUserOperationButtonsListener() {
    document.getElementById('update-button').addEventListener('click', () => {
        if(!confirm("登録しますか？")) return;
        const stockIdList = stockView.getUsersStockIdList();

        const inputElement = document.getElementById('users-stocks-data');
        const data = JSON.stringify(stockIdList);
        
        inputElement.value = data;
        document.getElementById('update-users-stocks').submit();
    })

    document.getElementById("up-button").addEventListener('click', () => stockView.up());
    document.getElementById("down-button").addEventListener('click', () => stockView.down());
    document.getElementById("select-reset-button").addEventListener('click', () => stockView.deselect());
}
