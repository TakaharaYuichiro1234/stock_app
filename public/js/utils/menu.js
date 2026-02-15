class Menu {
    constructor({ menuBtnId, menuPanelId, items = [] }) {
        this.menuBtn = document.getElementById(menuBtnId);
        this.menuPanel = document.getElementById(menuPanelId);
        this.items = items;
    }

    init() {
        this.render();
        this.initListeners();
    }

    render() {
        this.menuPanel.innerHTML = '';

        for (const item of this.items) {
            const el = item.createElement();
            if (item.type === 'action') {
                el.addEventListener('click', () => {
                    this.close();
                    item.action?.();
                });
            }
            this.menuPanel.appendChild(el);
        }
    }

    initListeners() {
        this.menuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.menuPanel.classList.toggle('show');
        });

        document.addEventListener('click', () => this.close());
    }

    close() {
        this.menuPanel.classList.remove('show');
    }
}
