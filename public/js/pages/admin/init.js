let stockView;

function init() {
    initMenu();

    initRegistrationEvents();

    stockView = new StocksViewModule();
    initEventsFromStockView();
    initModalScreenEvents();
    initRegisteredStocksSection();
}

function initMenu() {
    const items = [];

    if (isAdmin) {
        items.push(
            new MenuItem({
                caption: '🛡️全ての銘柄の株価を更新',
                name: 'update-stock-prices-all',
                action: () => {
                    if (confirm('全銘柄の最新の株価を追加しますか？')) document.getElementById('update-stock-prices-all').submit();
                }
            })
        );
    }

    items.push(
        new MenuItem({
            caption: 'ログアウト',
            name: 'logout',
            action: () =>  document.getElementById('logout').submit()
        })
    );

    const menu = new Menu({
        menuBtnId: 'menu-btn',
        menuPanelId: 'menu-panel',
        items
    });

    menu.init();
}

// stockViewModuleからのイベントを受ける
function initEventsFromStockView() {
    // 株価更新
    document.addEventListener("update-prices", async (e) => {
        const { stockId } = e.detail;
        const url = `${BASE_PATH}/api/stocks/update-stock-prices`;
        try {
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content');

            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('stockId', stockId);

            const res = await fetch(url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin', // セッション / CSRF用
            });

            if (!res.ok) {
                throw new Error('通信エラー');
            }

            const result = await res.json();

            if (!result.success) throw new Error('書き込みエラー');

            alert('株価を更新しました');

        } catch (err) {
            console.error(err);
            alert('株価更新に失敗しました');
        }
    });

    document.addEventListener("edit-stock", async (e) => {
        const { stockId } = e.detail;

        // 編集用画面に表示するために、現在の登録情報をAPIから取得
        let stock = null;
        try {
            const res = await fetch(`${BASE_PATH}/api/stocks/get/${stockId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!res.ok) {
                throw new Error('通信エラー');
            }

            const result = await res.json();
            if (!result.success) throw new Error('書き込みエラー');

            stock = result.stock;

        } catch (err) {
            console.error(err);
            return; 
        }

        // モーダル画面にデータを設定して、銘柄編集画面を表示
        document.getElementById('input-stock-name').value = stock.name;
        document.getElementById('input-digit').value = stock.digit;
        document.getElementById('modal-form-stock-id').value = stockId;

        document.querySelector(".modal").classList.remove("hidden");
    });

    // 登録銘柄削除
    document.addEventListener("remove-stock", async (e) => {
        if (!confirm('この銘柄を削除しますか？')) return;

        const { stockId } = e.detail;
        const url = `${BASE_PATH}/api/stocks/delete`;
        try {
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content');

            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('stockId', stockId);

            const res = await fetch(url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin', // セッション / CSRF用
            });

            if (!res.ok) {
                throw new Error('通信エラー');
            }

            const result = await res.json();

            if (!result.success) throw new Error('書き込みエラー');
            await refreshSearchedStocks("");

        } catch (err) {
            console.error(err);
            alert('削除に失敗しました');
        }
    });

    // 銘柄詳細画面に遷移
    document.addEventListener("show-detail", (e) => {
        const { stockId } = e.detail;
        const redirectUri = encodeURI(`${BASE_PATH}/admins`);
        location.href=`${BASE_PATH}/stocks/show-detail/${stockId}?redirect=${redirectUri}`
    });
}

function initModalScreenEvents() {
    // モーダル画面の閉じるボタン
    document.querySelector(".modal-close").addEventListener("click", () => {
        document.querySelector(".modal").classList.add("hidden");
    });

    // モーダル画面の更新ボタンを押した時の処理
    document.getElementById('modal-form').addEventListener('submit', async (e) => {
        e.preventDefault(); 
        console.log("trap0 ");

        const form = e.target;
        const formData = new FormData(form);

        // バリデーションチェック
        const name = formData.get('name');
        const digit = formData.get('digit');
        const validationErrors = [];

        if (name === "") validationErrors.push("名前を入力して下さい");
        if (name.length > 255) validationErrors.push("名前は255文字以下で入力して下さい");
        if (!(/^\d+$/.test(digit))) validationErrors.push("桁数は正の整数を入力してください");

        if (validationErrors.length > 0) {
            showModalMessages(validationErrors.map(err => ({'message': err, 'type':'error'})));
            return;
        } 

        // 更新処理
        showModalMessages([]);

        console.log("trap1 ");


        const url = `${BASE_PATH}/api/stocks/update`;
        try {
            // const csrfToken = document
            //     .querySelector('meta[name="csrf-token"]')
            //     ?.getAttribute('content');

            // const formData = new FormData();
            // formData.append('csrf_token', csrfToken);
            // formData.append('stockId', stockId);

            const res = await fetch(url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin', // セッション / CSRF用
            });

            console.log("res: ", res);

            if (!res.ok) {
                throw new Error('通信エラー');
            }

            const result = await res.json();
            console.log("result: ", result);

            if (!result.success) throw new Error('書き込みエラー');



            // 画面更新処理
            await refreshSearchedStocks("");
            alert('更新しました');

        } catch (err) {
            console.error(err);
            alert('更新に失敗しました');
        }



        // form.submit();
        document.querySelector(".modal").classList.add("hidden");

    });
}

function showModalMessages(messageObjects) {  // messageObjects: {message: string, type: string(error/success)}
    const messageContainer = document.getElementById("modal-message-container");
    messageContainer.innerHTML = '';
    for (obj of messageObjects) {
        const element = document.createElement('p');
        element.textContent = obj.message;
        element.className = obj.type;
        messageContainer.appendChild(element);
    }
}

function initRegistrationEvents() {
    // 新規銘柄登録
    document.getElementById('stockForm').addEventListener('submit', async (e) => {
        e.preventDefault(); 

        const form = e.target;
        const url  = form.action;
        const formData = new FormData(form);

        // バリデーションチェック
        const name = formData.get('name');
        const digit = formData.get('digit');
        const validationErrors = [];

        if (name === "") validationErrors.push("名前を入力して下さい");
        if (name.length > 255) validationErrors.push("名前は255文字以下で入力して下さい");
        if (!(/^\d+$/.test(digit))) validationErrors.push("桁数は正の整数を入力してください");

        if (validationErrors.length > 0) {
            showMessages(validationErrors.map(err => ({'message': err, 'type':'error'})));
            return;
        } else {
            showMessages([]);
        }

        // 新規銘柄登録処理
        try {
            const res = await fetch(url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin', // セッション / CSRF用
            });

            if (!res.ok) {
                throw new Error('通信エラー');
            }

            const result = await res.json();
            if (!result.success) {
                throw new Error('登録エラー');
            }

            await refreshSearchedStocks("");
            document.getElementById('formSubmit').toggleAttribute('disabled', true);
            alert('登録しました');

        } catch (err) {
            console.error(err);
            alert('登録に失敗しました');
        }
    });

    // 新規銘柄検索
    document.getElementById('search-new-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        showMessages([{message: '検索中...', type: 'nomal'}]);
        
        try {
            const form = e.target;
            const formData = new FormData(form);
            const input = formData.get('symbol').trim();
            const params = new URLSearchParams({ keywords: input });

            const res = await fetch(`${BASE_PATH}/api/admins/show?${params}`, {
                headers: { Accept: 'application/json' }
            });

            if (!res.ok) {
                showSearchResult([`サーバーエラー（${res.status}）`], null);
                return;
            }

            const data = await res.json();

            if (!data.success) {
                showSearchResult(data.errors, null);
                return;
            }

            showSearchResult([], data.data, data.isRegistered);

        } catch (e) {
            showSearchResult(['通信エラーが発生しました'], null);
        }
    });
}

function showSearchResult(errors, data, isRegistered=false){
    const resultContainer = document.querySelector(".content-container");
    
    if (errors?.length) {
        showMessages(errors.map(err => ({message: err, type:'error'})));
        resultContainer.classList.add("hidden");
        return;
    } 

    if (!data) {
        showMessages([{message: '検索結果がありません。', type: 'nomal'}]);
        resultContainer.classList.add("hidden");
        return;
    }

    resultContainer.classList.remove("hidden");
    document.getElementById('formSubmit').toggleAttribute('disabled', isRegistered);
    showMessages(isRegistered ? [{message: 'この銘柄はすでに登録されています', type: 'error'}]: []);

    const fields = {
        'result-symbol': data.symbol,
        'result-date': data.date,
        'result-open': Number(data.open).toLocaleString('ja-JP'),
        'result-high': Number(data.high).toLocaleString('ja-JP'),
        'result-low': Number(data.low).toLocaleString('ja-JP'),
        'result-close': Number(data.close).toLocaleString('ja-JP'),
        'result-volume': Number(data.volume).toLocaleString('ja-JP'),
    };

    Object.entries(fields).forEach(([id, value]) => {
        document.getElementById(id).textContent = value;
    });

    const inputs = {
        name: data.shortName,
        digit: judgeDigit([data.open, data.high, data.low, data.close]),
        symbol: data.symbol,
        short_name: data.shortName,
        long_name: data.longName,
    };

    Object.entries(inputs).forEach(([id, value]) => {
        document.getElementById(id).value = value;
    });
}

function showMessages(messageObjects) {  // messageObjects: {message: string, type: string(error/success)}
    const messageContainer = document.getElementById("message-container");
    messageContainer.innerHTML = '';
    for (obj of messageObjects) {
        const element = document.createElement('p');
        element.textContent = obj.message;
        element.className = obj.type;
        messageContainer.appendChild(element);
    }
}

function judgeDigit(numberArray) {
    let maxDecimalPointLength = 0;
    const getDecimalPointLength = (number) => {
        const numbers = String(number).split('.');
        return numbers[1] ? numbers[1].length : 0;
    }

    for(number of numberArray) {
        const decimalPointLength = getDecimalPointLength(number);
        if (maxDecimalPointLength < decimalPointLength) maxDecimalPointLength = decimalPointLength;
    }

    if (maxDecimalPointLength > 2) maxDecimalPointLength = 2;

    return maxDecimalPointLength;
}


async function initRegisteredStocksSection() {
    // DB登録済み銘柄のリストを表示
    await refreshSearchedStocks("");

    document.getElementById("search-registered-form").addEventListener('submit', async (e) =>{
        e.preventDefault(); 

        const form = e.target;
        const formData = new FormData(form);
        const keywordInputs = formData.get('keyword').trim();
        await refreshSearchedStocks(keywordInputs);
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
    stockView.initFirstStockView(searchedStocks, listDomId, 'admin');
}