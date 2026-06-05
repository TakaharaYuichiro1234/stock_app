let dailyAssetsTotal = [];

export function setSummaryData(dailyAssetsTotalLocal) {
    dailyAssetsTotal = dailyAssetsTotalLocal;
}

export function showSummary() {
    const len = dailyAssetsTotal.length;
    const iniObj = {'evaluate-value': 0, 'total_profit_loss':0, 'total_asset_value':0, 'total_realize':0, 'total_dividend':0};

    const selectedDate = document.getElementById('summary-date').value;
    const targetDataIndex = dailyAssetsTotal.findIndex(datum => datum.date === selectedDate);
    const latest = targetDataIndex >=0 ? dailyAssetsTotal[targetDataIndex] : iniObj;
    const second = targetDataIndex >=1 ? dailyAssetsTotal[targetDataIndex-1] : iniObj;


    putValue('evaluate-value', latest['total_asset_value']);

    putValue('profit', latest['total_profit_loss'], 0, true, false);

    putValue(
        'profit-ratio', 
        latest['total_asset_value']!=0 ? latest['total_profit_loss']/latest['total_asset_value']*100 : 0, 
        2, 
        true, 
        true
    );

    putValue('total-profit', latest['total_profit_loss'] + latest['total_realize'] + latest['total_dividend'], 0, true, false);

    putValue('realize', latest['total_realize'], 0, true, false);

    putValue('dividend', latest['total_dividend'], 0, false, false);


    putValue('evaluate-value-diff', latest['total_asset_value']-second['total_asset_value'], 0, true, false);

    putValue('profit-diff', latest['total_profit_loss']-second['total_profit_loss'], 0, true, false);

    putValue(
        'profit-ratio-diff', 
        (latest['total_asset_value']!=0 ? latest['total_profit_loss']/latest['total_asset_value']*100 : 0)
        -(second['total_asset_value']!=0 ? second['total_profit_loss']/second['total_asset_value']*100 : 0),
        2, 
        true, 
        true
    );

    putValue('realize-diff', latest['total_realize']-second['total_realize'], 0, true, false);

    putValue('dividend-diff', latest['total_dividend']-second['total_dividend'], 0, true, false);

    putValue(
        'total-profit-diff', 
        (latest['total_profit_loss'] + latest['total_realize'] + latest['total_dividend'])-(second['total_profit_loss'] + second['total_realize'] + second['total_dividend']),
        0, 
        true, 
        false
    );
}

function putValue(elementId, value, digit = 0, withSign = false, isPercentage = false){
    const formatted = (n, digit) => {
        return n.toLocaleString(undefined, {minimumFractionDigits: digit, maximumFractionDigits: digit});
    };

    const sign = value > 0 ? "+": value == 0 ? "±": "";
    const prefix = withSign ? sign : "";
    const suffix = isPercentage ? "%" : "";

    const color1 = (n) => n>0 ? "red" : n==0 ? "white": "green"; 

    const element = document.getElementById(elementId);
    element.textContent = prefix + formatted(value, digit) + suffix;
    element.style.color = withSign ? color1(value) : "white";
}
