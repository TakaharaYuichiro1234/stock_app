export function showVariousPanels() {
    const main = document.getElementById('various-panels');
    main.innerHTML = '';
    main.appendChild(summary());
    main.appendChild(chart('profit-line', '損益推移'));
    main.appendChild(chart('profit-bar', '銘柄別評価損益'));
    main.appendChild(chart('expected-dividend-bar', '見込配当(本年度分)'));
    main.appendChild(chart('scatter', '評価損益-見込配当'));
    main.appendChild(chart('asset-pie', '資産内訳', 'withSelect'));
}

function summary() {
    // ヘッダー
    const hederTitle = document.createElement('h2');
    hederTitle.textContent = "サマリー";

    const inputDate = document.createElement('input');
    inputDate.type = 'date';
    inputDate.id = 'summary-date';
    
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    const formatToday = `${yyyy}-${mm}-${dd}`;
    inputDate.value = formatToday;

    const divContainerHeader = document.createElement('div');
    divContainerHeader.className = "container-header";
    divContainerHeader.appendChild(hederTitle);
    divContainerHeader.appendChild(inputDate);
    
    // データ表示部
    const partEvaluateValue = createSummaryPart("withDiff", "evaluate-value", "評価額");
    const partProfit = createSummaryPart("withDiff", "profit", "評価損益");
    const partProfitRatio = createSummaryPart("withDiff", "profit-ratio", "評価損益率");
    
    const partRealize = createSummaryPart("withDiff", "realize", "実現損益累計");
    const partDividend = createSummaryPart("withDiff", "dividend", "配当累計");
    const partTotalProfit = createSummaryPart("withDiff", "total-profit", "合計損益");

    const div1 = document.createElement('div');
    div1.className = "summary-container-inner";
    div1.appendChild(partEvaluateValue);
    div1.appendChild(partProfit);
    div1.appendChild(partProfitRatio);

    const div2 = document.createElement('div');
    div2.className = "summary-container-inner";
    div2.appendChild(partRealize);
    div2.appendChild(partDividend);
    div2.appendChild(partTotalProfit);

    const divContainerBody = document.createElement('div');
    divContainerBody.className = "summary-container";
    divContainerBody.appendChild(div1);
    divContainerBody.appendChild(div2);

    // サマリーパーツの各要素をコンテナにまとめる
    const divContainer = document.createElement('div');
    divContainer.className = "parts-container";
    divContainer.id = "parts_summary";
    divContainer.appendChild(divContainerHeader);
    divContainer.appendChild(divContainerBody);

    return divContainer;
}

function createSummaryPart(type, id, caption) {
    const pCaption = document.createElement('p');
    pCaption.className = "caption";
    pCaption.textContent = caption;

    const pData = document.createElement('p');
    pData.className = "data";
    pData.id = id;

    const pDiffCaption = document.createElement('p');
    pDiffCaption.className = "sup";
    pDiffCaption.textContent = "対前日";

    const pDiff = document.createElement('p');
    pDiff.className = "diff";
    pDiff.id = id + "-diff";

    const divDiff = document.createElement('div');
    divDiff.className = 'diff-block';
    divDiff.appendChild(pDiffCaption);
    divDiff.appendChild(pDiff);


    const div1 = document.createElement('div');
    div1.className = "data-container";
    div1.appendChild(pData);
    // if (type==="withDiff") div1.appendChild(pDiffCaption);
    // if (type==="withDiff") div1.appendChild(pDiff);
    if (type==="withDiff") div1.appendChild(divDiff);

    const div0 = document.createElement('div');
    div0.className = "summary-parts";
    div0.appendChild(pCaption);
    div0.appendChild(div1);

    return div0;
}

function chart(id, title, type='withoutSelect') {
    const canvas1 = document.createElement('canvas');
    canvas1.id = id + '-chart';

    const divContainerBody = document.createElement('div');
    divContainerBody.className = 'chart-container';
    divContainerBody.id = id + '-chart-container'
    divContainerBody.appendChild(canvas1);


    const option1 = document.createElement('option');
    option1.value = 'perchasedValue';
    option1.textContent = '取得金額'

    const option2 = document.createElement('option');
    option2.value = 'evaluatedValue';
    option2.textContent = '評価額'

    const headerSelect = document.createElement('select');
    headerSelect.className = 'asset-type';
    headerSelect.id = 'asset-type-select';
    headerSelect.appendChild(option1);
    headerSelect.appendChild(option2);


    const hederTitle = document.createElement('h2');
    hederTitle.textContent = title;

    const divContainerHeader = document.createElement('div');
    divContainerHeader.className = "container-header";
    divContainerHeader.appendChild(hederTitle);
    if (type === 'withSelect') divContainerHeader.appendChild(headerSelect);


    const divContainer = document.createElement('div');
    divContainer.className = "parts-container";
    divContainer.id = 'parts_' + id;
    divContainer.appendChild(divContainerHeader);
    divContainer.appendChild(divContainerBody);

    return divContainer;

}