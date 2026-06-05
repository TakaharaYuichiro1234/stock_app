



// ===========================================================
// Common Functions
// ===========================================================
export function sortTrades(data, key, asc) {
    return data.sort((a, b) => {
        let aValue = a[key];
        let bValue = b[key];

        // tentative対応
        if (key === 'profitAndLoss' || key === 'evaluatedValue') {
            aValue = a.tentative === 1 ? -Infinity : aValue;
            bValue = b.tentative === 1 ? -Infinity : bValue;
        }

        if (typeof aValue === 'string') {
            return asc ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
        }

        return asc ? aValue - bValue : bValue - aValue;
    });
}



export function getSelectedItems() {
    const selectedStockIds = [...document.getElementById('filterBox').querySelectorAll(".item:checked")].map(cb => cb.value);
    const selectedAccountIds = [...document.getElementById('accountFilterBox').querySelectorAll(".item:checked")].map(cb => cb.value);

    return [selectedStockIds, selectedAccountIds];
}
