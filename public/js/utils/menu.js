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





// function setMenuItem(caption=null, name=null, action=null, type='action') {
//     return {
//         caption: caption,
//         name: name,
//         action: action,
//         type: type,
//     };
// }

// function initMenuItems(menuItems) {
//     for (const menuItem of menuItems) {
//         createMenuElement(menuItem);
//     }
//     initMenuButtonListener(menuItems);
// }

// function createMenuElement(menuItem) {
//     let newElement;
//     if (menuItem.type==='action') {
//         newElement = document.createElement("div");
//         newElement.className = "menu-item";
//         newElement.innerHTML= menuItem.caption;
//         newElement.setAttribute('data-action', menuItem.name)
//     } else {
//         newElement = document.createElement("hr");   
//     }
    
//     const menuPanel = document.getElementById("menu-panel");
//     menuPanel.appendChild(newElement);
// }

// function initMenuButtonListener(menuItems) {
//     const menuBtn = document.getElementById("menu-btn");
//     const menuPanel = document.getElementById("menu-panel");

//     menuBtn.addEventListener("click", (e) => {
//         e.stopPropagation();
//         menuPanel.classList.toggle("show");
//     });

//     // 画面のどこかをクリックしたら閉じる
//     document.addEventListener("click", () => {
//         menuPanel.classList.remove("show");
//     });

//     // メニュー項目がクリックされたとき
//     document.querySelectorAll(".menu-item").forEach(item => {
//         item.addEventListener("click", (e) => {
//             menuPanel.classList.remove("show");
//             const action = e.target.dataset.action;

//             const index = menuItems.findIndex(item => item.name === action);
//             if (index>=0) {
//                 menuItems[index].action();
//             }
//         });
//     });
// }
