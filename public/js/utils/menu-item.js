class MenuItem {
    constructor({ caption = null, name = null, action = null, type = 'action' }) {
        this.caption = caption;
        this.name = name;
        this.action = action;
        this.type = type;
    }

    static divider() {
        return new MenuItem({ type: 'divider' });
    }

    createElement() {
        if (this.type === 'divider') {
            return document.createElement('hr');
        }

        const el = document.createElement('div');
        el.className = 'menu-item';
        el.textContent = this.caption;
        el.dataset.action = this.name;
        return el;
    }
}
