
class StocksViewModule{
    #searchedStocks= [];
    #usersStocks= [];
    #mSelectedId= -1;
    #searchedContainerId;
    #usersContainerId;

    constructor() {}

    // ==================================================================
    // パブリックメソッド
    // ==================================================================
    initFirstStockView(stocks, containerId, type = "searched") {
        this.#searchedStocks = stocks;
        this.#searchedContainerId = containerId;
        const stockList = document.getElementById(containerId);
        stockList.innerHTML = '';

        for (const stock of this.#searchedStocks) {
            const stockBoard = this.#createStockBoard(stock, type);
            stockList.appendChild(stockBoard);
        }
    }

    initSecondStockView(stocks, containerId, type = "users") {
        this.#usersStocks = stocks;
        this.#usersContainerId = containerId;
        const stockList = document.getElementById(containerId);
        stockList.innerHTML = '';

        for (const stock of stocks) {
            const stockBoard = this.#createStockBoard(stock, type);
            stockList.appendChild(stockBoard);
            this.#setDisableAddButton(stock.id);
        }
    }

    deselect() {
        this.#mSelectedId = -1;
        const usersStockList = document.getElementById(this.#usersContainerId);
        Array.from(usersStockList.children).forEach(element => element.style.backgroundColor = "#000");
    }

    up(){
        if (this.#mSelectedId < 0) return;
        const index = this.#usersStocks.findIndex(s => s.id === this.#mSelectedId);
        if (index === 0) return;    // 一番上なら動けない

        const replacedStockId = this.#usersStocks[index-1]['id'];

        [this.#usersStocks[index], this.#usersStocks[index-1]] = [this.#usersStocks[index-1], this.#usersStocks[index]];

        // 例：リストの「要素2」を「要素1」の前に移動する
        const parent = document.getElementById(this.#usersContainerId);
        const item1 = document.getElementById(`users-stock_${replacedStockId}`);
        const item2 = document.getElementById(`users-stock_${this.#mSelectedId}`);

        // item2をitem1の前に移動
        parent.insertBefore(item2, item1);
    }

    down(){
        if (this.#mSelectedId < 0) return;
        const index = this.#usersStocks.findIndex(s => s.id === this.#mSelectedId);
        if (index === this.#usersStocks.length-1) return;    // 一番下なら動けない

        const replacedStockId = this.#usersStocks[index+1]['id'];

        [this.#usersStocks[index], this.#usersStocks[index+1]] = [this.#usersStocks[index+1], this.#usersStocks[index]];

        // 例：リストの「要素2」を「要素1」の前に移動する
        const parent = document.getElementById(this.#usersContainerId);
        const item2 = document.getElementById(`users-stock_${replacedStockId}`);
        const item1 = document.getElementById(`users-stock_${this.#mSelectedId}`);

        // item2をitem1の前に移動
        parent.insertBefore(item2, item1);
    }

    getUsersStockIdList() {
        return this.#usersStocks.map(s => s.id);
    }

    // ==================================================================
    // プライベートメソッド
    // ==================================================================
    #createStockBoard(stock, type) {
        let boardClass;
        let boardAction;
        let buttonClass;
        let buttonText;
        let buttonAction;
        let buttonAnimation;

        let button2Text;
        let button2Action;
        let button3Text;
        let button3Action;
        
        switch (type) {
            case "admin":
                boardClass = 'searched-stock';
                boardAction = (id) => this.#showDetailAction(id);
                buttonClass = 'admin-button';
                buttonText = '株価更新';
                buttonAction = (id) => this.#updatePricesForAdmin(id);
                button2Text = '編集';
                button2Action = (id) => this.#editStockForAdmin(id);
                button3Text = '削除';
                button3Action = (id) => this.#removeStockForAdmin(id);
                break;

            case "searched":
                boardClass = 'searched-stock';
                boardAction = (id) => this.#showDetailAction(id);
                buttonClass = 'add-button';
                buttonText = '追加';
                buttonAction = (id) => this.#addAction(id);
                buttonAnimation = (id, ele) => this.#slideAnimation(id, ele);
                break;

            case "users":
                boardClass = 'users-stock';
                boardAction = (id) => this.#selectAction(id);
                buttonClass = 'remove-button';
                buttonText = '削除';
                buttonAction = (id) => this.#removeAction(id);
                break;

            default:
                return null;
        }


        const stockBoard = document.createElement('div');
        stockBoard.className = "stock-board";
        stockBoard.classList.add('stock-board', boardClass);
        stockBoard.id = `${boardClass}_${stock['id']}`;
        stockBoard.addEventListener('click', () => {
            boardAction(stock['id']);
        });

        const stockBoardNameBlock = document.createElement('div');
        stockBoardNameBlock.className = "stock-board-name-block";

        const stockBoardName = document.createElement('p');
        stockBoardName.className = "stock-board-name";
        stockBoardName.textContent = stock['name'];

        const stockBoardInfoBlock = document.createElement('div');
        stockBoardInfoBlock.className = "stock-board-info-block";

        const stockBoardSymbol = document.createElement('p');
        stockBoardSymbol.className = "stock-board-symbol";
        stockBoardSymbol.textContent = stock['symbol'];

        stockBoardInfoBlock.appendChild(stockBoardSymbol);
        stockBoardNameBlock.appendChild(stockBoardName);
        stockBoardNameBlock.appendChild(stockBoardInfoBlock);

        const stockBoardButtonContainer = document.createElement('div');
        stockBoardButtonContainer.className = "stock-board-button-container";

        const button = document.createElement('button');
        button.className = buttonClass;
        button.textContent = buttonText;
        button.addEventListener('click', (e)=> {
            e.stopPropagation();
            if (buttonAnimation) buttonAnimation(stock['id'], stockBoard);
            buttonAction(stock['id']);
        })

        stockBoardButtonContainer.appendChild(button);

        if (button2Action) {
            const button2 = document.createElement('button');
            button2.className = buttonClass;
            button2.textContent = button2Text;
            button2.addEventListener('click', (e)=> {
                e.stopPropagation();        
                button2Action(stock['id']);
            })

            stockBoardButtonContainer.appendChild(button2);
        }

        if (button3Action) {
            const button3 = document.createElement('button');
            button3.className = buttonClass;
            button3.textContent = button3Text;
            button3.addEventListener('click', (e)=> {
                e.stopPropagation();        
                button3Action(stock['id']);
            })

            stockBoardButtonContainer.appendChild(button3);
        }

        stockBoard.appendChild(stockBoardNameBlock);
        stockBoard.appendChild(stockBoardButtonContainer);
        
        return stockBoard;
    }

    #slideAnimation(stockId, element) {
        if (!this.#isRegistered(stockId)) {
            element.classList.add('slide-out-right');
                setTimeout(() => {
                element.classList.remove('slide-out-right');
            }, 350);
        }
    }

    #showDetailAction(stockId){
        document.dispatchEvent(new CustomEvent('show-detail', {
            detail: { stockId: stockId }
        }))
    }

    #isRegistered(stockId) {
        const index = this.#usersStocks.findIndex(s => s.id === stockId);
        return index>=0;
    }

    #setDisableAddButton(stockId) {
        const targetBoard = document.getElementById(`searched-stock_${stockId}`);
        const descendantButton = targetBoard.querySelector('.add-button');
        if (descendantButton) descendantButton.disabled = this.#isRegistered(stockId);
    }

    #addAction(stockId) {    
        if (this.#isRegistered(stockId)) {
            alert('この銘柄は登録済みです。');
            return;
        }

        const targetSearchedStock = this.#searchedStocks.find(stock => stock.id === stockId);
        if (!targetSearchedStock) return;

        const stock = {
            id: targetSearchedStock['id'],
            name: targetSearchedStock['name'],
            symbol: targetSearchedStock['symbol']
        }
        this.#usersStocks.push(stock);

        const stockBoard = this.#createStockBoard(stock, 'users');

        const stockList = document.getElementById(this.#usersContainerId);
        stockList.appendChild(stockBoard);

        this.#setDisableAddButton(stockId)
    }

    #selectAction(stockId) {
        this.deselect();
        this.#mSelectedId = stockId;
        const targetBoard = document.getElementById(`users-stock_${stockId}`);
        targetBoard.style.backgroundColor = "#ddaaaa";
    }

    #removeAction(stockId) {
        const usersStockList = document.getElementById(this.#usersContainerId);
        const targetChild = document.getElementById(`users-stock_${stockId}`);
        usersStockList.removeChild(targetChild);

        this.#usersStocks = this.#usersStocks.filter(s => s.id !== stockId);
        this.#setDisableAddButton(stockId)
    }

    #updatePricesForAdmin(stockId) {    
        document.dispatchEvent(new CustomEvent('update-prices', {
            detail: { stockId: stockId }
        }))
    }

    #editStockForAdmin(stockId) {    
        document.dispatchEvent(new CustomEvent('edit-stock', {
            detail: { stockId: stockId }
        }))
    }

    #removeStockForAdmin(stockId) {    
        document.dispatchEvent(new CustomEvent('remove-stock', {
            detail: { stockId: stockId }
        }))
    }
}
