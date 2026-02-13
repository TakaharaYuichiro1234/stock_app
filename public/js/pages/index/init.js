function init() {
    initEvents();
    initMenu();
}

function initEvents() {
    for (stock of stocks) {
        const id = stock['id'];
        document.getElementById(`list-content_${id}`).addEventListener('click', () => {
            window.location.href = `${BASE_PATH}/stocks/show-detail/${id}`;
        })
    }
}
    
function initMenu() {
    const items = [];

    if (isAdmin) {
        items.push(
            new MenuItem({
                caption: '🛡️管理画面',
                name: 'admin',
                action: () => location.href = `${BASE_PATH}/admins`
            })
        );
    }

    if (user) {
        items.push(
            new MenuItem({
                caption: 'マイ銘柄編集',
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
