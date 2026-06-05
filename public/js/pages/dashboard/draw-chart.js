import { sortTrades } from '../dashboard/common-function.js';
// import ChartDataLabels from 'chartjs-plugin-datalabels';

// Chart.register(ChartDataLabels);


// ===========================================================
// chart
// ===========================================================
let profitLineChart = null;
let profitBarChart = null;
let assetPieChart = null;
let expectedDividendBarChart = null;
let scatterChart = null;

let dailyAssetsTotal = null;
let totalizedData = null;

export function setChartData(dailyAssetsTotal0, totalizedData0) {
    dailyAssetsTotal = dailyAssetsTotal0;
    totalizedData = totalizedData0;
}
export function drawChart() {
    drawProfitLineChart();
    drawProfitBarChart();
    drawAssetPieChart();
    drawExpectedDividendBarChart();
    drawScatterChart();
}

function drawProfitLineChart() {
    const days = dailyAssetsTotal.map(t => t.date);
    const profitLoss = dailyAssetsTotal.map(t => t.total_profit_loss);
    const realize = dailyAssetsTotal.map(t => t.total_realize);
    const dividend = dailyAssetsTotal.map(t => t.total_dividend);
    const total = dailyAssetsTotal.map(t => t.total_profit_loss + t.total_realize + t.total_dividend);

    const labels = days.map((date) => new Date(date));

    const datasets = [
        {
            label: '評価損益',
            data: profitLoss,
            borderColor: 'rgba(255, 120, 120, 1)',
            backgroundColor: 'rgba(255, 120, 120, 1)',
            // pointStyle: 'circle',
            pointRadius: 0,
            borderWidth: 2,
        },
        {
            label: '実現損益累計',
            data: realize,
            borderColor: 'rgba(200, 255, 180, 1)',
            backgroundColor: 'rgba(200, 255, 180, 1)',
            // pointStyle: 'circle',
            pointRadius: 0,
            borderWidth: 1,
        },
        {
            label: '配当累計',
            data: dividend,
            borderColor: 'rgba(180, 250, 255, 1)',
            backgroundColor: 'rgba(180, 250, 255, 1)',
            // pointStyle: 'circle',
            pointRadius: 0,
            borderWidth: 1,
        },
        {
            label: '合計',
            data: total,
            borderColor: 'white',
            backgroundColor: 'white',
            pointStyle: 'circle',
            pointRadius: 0,
            borderWidth: 4,
        }
    ];

    if (profitLineChart) {
        profitLineChart.destroy();
    }

    const ctx = document.getElementById('profit-line-chart').getContext('2d');

    profitLineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                x: {
                    type: 'time',
                    ticks: {
                        color: 'white' 
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        // 値が0の線だけ色を変える
                        color: (context) => {
                        if (context.tick.value === 0) {
                            return 'rgba(255, 255, 255, 0.5)'; // 0の線の色
                        }
                        return 'rgba(0, 0, 0, 0.1)'; // 通常のグリッド線の色
                        },
                        // 0の線の太さを強調したい場合
                        lineWidth: (context) => {
                        if (context.tick.value === 0) {
                            return 2; // 0の線は太く
                        }
                            return 1;
                        }
                    },
                    ticks: {
                        color: 'white',
                        callback: function(value) {
                            const abs = Math.abs(value);
                            if (abs >= 100000000) {
                                return (value / 100000000).toLocaleString() + '億';
                            } else if (abs >= 10000) {
                                return (value / 10000).toLocaleString() + '万';
                            }
                            return value;
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: '#AAA' 
                    }
                },
                datalabels: {
                    display: false
                }
            }
        },
    });
}

function drawProfitBarChart() {

    const result = [];
    for (const [stockId, obj] of totalizedData.entries()) {
        if (obj['total_quantity'] <= 0) continue;
        const targetStock = stocks.find(stock => String(stock.id) === String(stockId));
        if (!targetStock) continue;
        const evaluatedValue = (targetStock?.latest_close ?? 0) * obj['total_quantity'];
        result.push({
            name: targetStock?.name ?? '',
            profitAndLoss: evaluatedValue - obj['perchasedValue'],
        });
    }

    sortTrades(result, "profitAndLoss", false);

    const labels = result.map((r) => r.name);
    const data = result.map((r) => r.profitAndLoss);

    const datasets = [{
        label: '評価損益',
        data: data,
        borderColor: 'rgba(255, 120, 120, 1)',
        backgroundColor: 'rgba(255, 120, 120, 1)',
        pointStyle: 'rect',
    }];

    const optionsScaleY = {
        beginAtZero: true,
        grid: {
            // 値が0の線だけ色を変える
            color: (context) => {
            if (context.tick.value === 0) {
                return 'rgba(255, 255, 255, 0.5)'; // 0の線の色
            }
            return 'rgba(0, 0, 0, 0.1)'; // 通常のグリッド線の色
            },
            // 0の線の太さを強調したい場合
            lineWidth: (context) => {
            if (context.tick.value === 0) {
                return 2; // 0の線は太く
            }
                return 1;
            }
        }
    }

    if (profitBarChart) profitBarChart.destroy();

    const ctx = document.getElementById('profit-bar-chart').getContext('2d');
    profitBarChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                x: {
                    ticks: {
                        autoSkip: false,    // ラベルを間引かない
                        maxRotation: 90,    // ラベルを回転させない(水平)
                        minRotation: 90,     // ラベルを回転させない(水平)
                        color: 'white'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        // 値が0の線だけ色を変える
                        color: (context) => {
                        if (context.tick.value === 0) {
                            return 'rgba(255, 255, 255, 0.5)'; // 0の線の色
                        }
                        return 'rgba(0, 0, 0, 0.1)'; // 通常のグリッド線の色
                        },
                        // 0の線の太さを強調
                        lineWidth: (context) => {
                        if (context.tick.value === 0) {
                            return 2; // 0の線は太く
                        }
                            return 1;
                        }
                    },
                    ticks: {
                        color: 'white',
                        callback: function(value) {
                            const abs = Math.abs(value);
                            if (abs >= 100000000) {
                                return (value / 100000000).toLocaleString() + '億';
                            } else if (abs >= 10000) {
                                return (value / 10000).toLocaleString() + '万';
                            }
                            return value;
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: '#AAA' 
                    }
                },
                datalabels: {
                    display: false
                }
            },
        },
    });
}

export function drawAssetPieChart() {
    const assetType = document.getElementById('asset-type-select').value;
    
    const result = [];
    for (const [stockId, obj] of totalizedData.entries()) {
        if (obj['total_quantity'] <= 0) continue;
        const targetStock = stocks.find(stock => String(stock.id) === String(stockId));
        if (!targetStock) continue;
        const evaluatedValue = (targetStock?.latest_close ?? 0) * obj['total_quantity'];
        result.push({
            name: targetStock?.name ?? '',
            perchasedValue: obj['perchasedValue'],
            evaluatedValue: evaluatedValue,
        });
    }

    sortTrades(result, assetType, false);

    let result2 = [];
    if (result.length>10) {
        let othersTotal = 0;
        for(const [index,r] of Object.entries(result)) {
            if (index>10) {
                othersTotal += r[assetType];
            } else {
                result2.push({name: r['name'], value: r[assetType] });
            }
        }
        result2.push({name: 'others', value: othersTotal });
        
    } else {
        for(const r of result) {
            result2.push({name: r['name'], value: r[assetType] });
        }
    }

    const labels = result2.map((r) => r.name);
    const data = result2.map((r) => r.value);

    const datasets = [{
        data: data,
    }];

    if (assetPieChart) assetPieChart.destroy();

    const ctx = document.getElementById('asset-pie-chart').getContext('2d');
    assetPieChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            plugins: {
                legend: {
                    labels: {
                        // 文字色を黒に設定
                        color: '#DDD' ,
                        font: {
                            size: 9,
                        }
                    },
                },
                datalabels: {
                    formatter: (value, context) => {
                        const label = context.chart.data.labels[context.dataIndex];
                        return label;
                    },
                    color: '#333',
                }
            },
   
        },
    });
}

function drawExpectedDividendBarChart() {    
    const result = [...totalizedData].map(([stockId, obj])  => {
        const totalizedDatum = totalizedData.get(stockId);
        const totalQuantity = totalizedDatum['total_quantity'];

        const targetStock = stocks.find(stock => String(stock.id) === String(stockId));
        if (targetStock && (totalQuantity > 0)) {
            return {
                name: targetStock.name,
                expected_dividend: obj['expected_dividend'],
            }
        };
    }).filter(Boolean);;

    sortTrades(result, "expected_dividend", false);

    const labels = result.map((r) => r.name);
    const data = result.map((r) => r.expected_dividend);

    const datasets = [{
        label: '見込配当',
        data: data,
        borderColor: 'rgba(255, 120, 120, 1)',
        backgroundColor: 'rgba(255, 120, 120, 1)',
        pointStyle: 'rect',
    }];

    const optionsScaleY = {
        beginAtZero: true,
        grid: {
            // 値が0の線だけ色を変える
            color: (context) => {
            if (context.tick.value === 0) {
                return 'rgba(255, 255, 255, 0.5)'; // 0の線の色
            }
                return 'rgba(0, 0, 0, 0.1)'; // 通常のグリッド線の色
            },
            // 0の線の太さを強調
            lineWidth: (context) => {
                if (context.tick.value === 0) {
                    return 2; // 0の線は太く
                }
                    return 1;
            }
        }
    }

    if (expectedDividendBarChart) {
        expectedDividendBarChart.destroy();
    }

    const ctx = document.getElementById('expected-dividend-bar-chart').getContext('2d');
    expectedDividendBarChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                x: {
                    ticks: {
                        autoSkip: false,    // ラベルを間引かない
                        maxRotation: 90,    // ラベルを回転させない(水平)
                        minRotation: 90,     // ラベルを回転させない(水平)
                        color: 'white'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        // 値が0の線だけ色を変える
                        color: (context) => {
                        if (context.tick.value === 0) {
                            return 'rgba(255, 255, 255, 0.5)'; // 0の線の色
                        }
                        return 'rgba(0, 0, 0, 0.1)'; // 通常のグリッド線の色
                        },
                        // 0の線の太さを強調したい場合
                        lineWidth: (context) => {
                        if (context.tick.value === 0) {
                            return 2; // 0の線は太く
                        }
                            return 1;
                        }
                    },
                    ticks: {
                        color: 'white',
                        callback: function(value) {
                            const abs = Math.abs(value);
                            if (abs >= 100000000) {
                                return (value / 100000000).toLocaleString() + '億';
                            } else if (abs >= 10000) {
                                return (value / 10000).toLocaleString() + '万';
                            }
                            return value;
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false,
                    labels: {
                        color: '#AAA' 
                    }
                },
                datalabels: {
                    display: false
                }
            },
        },
    });



    const totalExpectedDividend = result.reduce((acc, cur) => acc + cur.expected_dividend, 0);

    const container = document.getElementById('expected-dividend-bar-chart-container');
    const label = document.createElement('p');
    label.textContent = Math.floor(totalExpectedDividend).toLocaleString();
    label.classList.add('chart_label');
    container.appendChild(label);

}


function drawScatterChart() {
    // const result = [];
    const data = [];
    for (const [stockId, obj] of totalizedData.entries()) {
        if (obj['total_quantity'] <= 0) continue;

        const targetStock = stocks.find(stock => String(stock.id) === String(stockId));
        if (!targetStock) continue;

        const evaluatedValue = (targetStock?.latest_close ?? 0) * obj['total_quantity'];
        const profitAndLoss = evaluatedValue - obj['perchasedValue'];
        // const expected_dividend = expected_dividends.find(ed => String(ed.stock_id) === String(stockId))?.expected_dividend * obj['total_quantity'] ?? 0;
        const expected_dividend = obj['expected_dividend'];

        // const datum = { x: profitAndLoss, y: expected_dividend };
        const datum = { x: profitAndLoss, y: expected_dividend, label: targetStock?.name ?? '' };
        data.push(datum);
    }

    // const labels = result.map((r) => r.name);
    // const data = result.map((r) => r.expected_dividend);

    const datasets = [{
        label: '見込配当',
        data: data,
        borderColor: 'rgba(255, 120, 120, 1)',
        backgroundColor: 'rgba(255, 120, 120, 1)',
        pointStyle: 'rect',
        // tension: 0.02
    }];

    const optionsScaleY = {
        beginAtZero: true,
        grid: {
            // 値が0の線だけ色を変える
            color: (context) => {
            if (context.tick.value === 0) {
                return 'rgba(255, 255, 255, 0.5)'; // 0の線の色
            }
            return 'rgba(0, 0, 0, 0.1)'; // 通常のグリッド線の色
            },
            // 0の線の太さを強調したい場合
            lineWidth: (context) => {
            if (context.tick.value === 0) {
                return 2; // 0の線は太く
            }
                return 1;
            }
        }
    }


    if (scatterChart) {
        scatterChart.destroy();
    }

    const ctx = document.getElementById('scatter-chart').getContext('2d');
    scatterChart = new Chart(ctx, {
        type: 'scatter',
        data: {
            // labels: labels,
            datasets: datasets
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: {
                        // 値が0の線だけ色を変える
                        color: (context) => {
                        if (context.tick.value === 0) {
                            return 'rgba(255, 255, 255, 0.5)'; // 0の線の色
                        }
                        return 'rgba(0, 0, 0, 0.1)'; // 通常のグリッド線の色
                        },
                        // 0の線の太さを強調したい場合
                        lineWidth: (context) => {
                        if (context.tick.value === 0) {
                            return 2; // 0の線は太く
                        }
                            return 1;
                        }
                    },
                    ticks: {
                        color: 'white',
                        callback: function(value) {
                            const abs = Math.abs(value);
                            if (abs >= 100000000) {
                                return (value / 100000000).toLocaleString() + '億';
                            } else if (abs >= 10000) {
                                return (value / 10000).toLocaleString() + '万';
                            }
                            return value;
                        }
                    },
                    title: {
                        display: true,
                        text: '評価損益[円]',
                        color: '#AAA',
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        // 値が0の線だけ色を変える
                        color: (context) => {
                        if (context.tick.value === 0) {
                            return 'rgba(255, 255, 255, 0.5)'; // 0の線の色
                        }
                        return 'rgba(0, 0, 0, 0.1)'; // 通常のグリッド線の色
                        },
                        // 0の線の太さを強調したい場合
                        lineWidth: (context) => {
                        if (context.tick.value === 0) {
                            return 2; // 0の線は太く
                        }
                            return 1;
                        }
                    },
                    ticks: {
                        color: 'white',
                        callback: function(value) {
                            const abs = Math.abs(value);
                            if (abs >= 100000000) {
                                return (value / 100000000).toLocaleString() + '億';
                            } else if (abs >= 10000) {
                                return (value / 10000).toLocaleString() + '万';
                            }
                            return value;
                        }
                    },
                    title: {
                        display: true,
                        text: '見込配当(今後1年間)[円]',
                        color: '#AAA',
                    }
                },


            },
            plugins: {
                legend: {
                    display: false,
                },
                datalabels: {
                    color: '#DDD',
                }

            },
        },
    });

}