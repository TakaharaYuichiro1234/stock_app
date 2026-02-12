class ChartModule{
    #domId;
    #chart;
    #series;
    #markers;

    constructor(domId) {
        this.#domId = domId;
        this.#chart = null;
        this.#series = null;
        this.#markers = [];
    }

    init() {
        const dom = document.getElementById(this.#domId);
        this.#chart = LightweightCharts.createChart(dom, {
            width: dom.clientWidth, 
            height: dom.clientHeight,

            layout: {
                background: {
                    color: '#131722',
                },
                textColor: 'rgba(255, 255, 255, 0.9)',
            },
                    
            grid: {
                vertLines: {
                    color: 'rgba(197, 203, 206, 0.2)',
                },
                horzLines: {
                    color: 'rgba(197, 203, 206, 0.2)',
                },
            },

            crosshair: {
                mode: LightweightCharts.CrosshairMode.Normal,
                vertLine: {
                    width: 8,
                    color: '#C3BCDB44',
                    style: LightweightCharts.LineStyle.Solid,
                    labelBackgroundColor: '#9B7DFF',
                },
                horzLine: {
                    color: '#9B7DFF',
                    labelBackgroundColor: '#9B7DFF',
                },
            },
            rightPriceScale: {
                visible: true,
                borderVisible: true,
                textColor: 'rgba(255, 255, 255, 0.9)',
                borderColor: 'rgba(197, 203, 206, 0.8)',
            },
            timeScale: {
                borderColor: 'rgba(197, 203, 206, 0.8)',
                timeVisible: false,                         
                secondsVisible: false, 
                rightBarStaysOnScroll: false,
                rightOffset: 5,  
                leftOffset: 2,
            },
            localization: {
                locale: 'ja-JP',
                dateFormat: 'MM-dd',
            },
        });

        this.#series = this.#chart.addSeries(LightweightCharts.CandlestickSeries, {
            upColor: '#ef5350',
            downColor: '#f5f3f9',
            borderVisible: false,
            wickUpColor: '#ef5350',
            wickDownColor: '#f5f3f9',
            priceFormat: {
                type: 'price',
                precision: 0,   // ← 小数点以下桁数
                minMove: 1 // ← precision と合わせる
            }
        });

        this.#chart.subscribeClick((param) => {
            // チャート外をクリックした場合
            if (!param.time) {
                return;
            }

            document.dispatchEvent(new CustomEvent('click-chart', {
                detail: { time: param.time }
            }))
        });

        LightweightCharts.createSeriesMarkers(this.#series, this.#markers);
    }


    resizeChart(width, height) {
        this.#chart.resize(width, height);
    }

    drawChart(prices, trades, monthRange) {
        this.#series.setData(prices);
        this.#chart.timeScale().fitContent();

        this.#markers.splice(0)
        for (const trade of trades) {
            this.#addMarker(trade);
        }

        this.#setTimeRange(monthRange);
    }

    #addMarker(data) {
        let position;
        let color;
        let shape;
        let text;

        if (data.type === 1) {
            position = 'belowBar';
            color = '#2196F3';
            shape = 'arrowUp';
            text = `Buy ${Number(data.total_quantity)}@${Math.floor(data.avg_price)}`;
        } else if (data.type === 2) {
            position = 'aboveBar';
            color = '#7bc4ad';
            shape = 'arrowDown';
            text = `Sell ${Number(data.total_quantity)}@${Math.floor(data.avg_price)}`;
        } else {
            return;
        }

        this.#markers.push({
            time: data.time,
            position: position,
            color: color,
            shape: shape,
            text: text,
        });
    }

    #setTimeRange(monthRange) {
        const now = new Date();

        const to = Math.floor(now.getTime() / 1000); // 今日（秒）
        
        const fromDate = new Date(now);
        fromDate.setMonth(fromDate.getMonth() - monthRange);
        const from = Math.floor(fromDate.getTime() / 1000);

        this.#chart.timeScale().setVisibleRange({
            from,
            to,
        });
    }
}
