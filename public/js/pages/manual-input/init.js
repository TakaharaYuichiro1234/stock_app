import { BASE_PATH } from '../../config.js';
import { getCsrfToken } from '../../utils/common.js';
import * as store from './store.js';

document.addEventListener("DOMContentLoaded", () => {
    init();
});


async function init() {
    initModal();
    initRegistrationEvents();
}
function initModal() {

    // モーダル画面表示・非表示
    document.getElementById("batch-input-button").addEventListener("click", () => {
        // document.getElementById('modal-update').classList.add("hidden");
        // document.getElementById('modal-submit').classList.remove("hidden");

        document.querySelector(".modal").classList.remove("hidden");
    });

    document.querySelector(".modal-close").addEventListener("click", () => {
        document.querySelector(".modal").classList.add("hidden");
    });

    document.getElementById("add-to-temporary-data").addEventListener("click", () => {
        store.addToTemporaryData();
        document.querySelector(".modal").classList.add("hidden");
    });
}

function initRegistrationEvents() {
    document.getElementById('temporary-save').addEventListener('click', function() {
        store.getInputData();
    });

    // 「貼り付け」クリック時の処理
    document.getElementById('paste-from-clipboard').addEventListener('click', async () => {
        await store.pasteFromClipboard();
    });

    document.getElementById('store-button').addEventListener('click', async () => {
        await store.storeData();
    });
}