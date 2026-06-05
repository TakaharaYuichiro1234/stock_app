import { BASE_PATH } from '../../config.js';
import { getCsrfToken } from '../../utils/common.js';

// ===========================================================
// データ取得
// ===========================================================
export async function fetchAssets(stockIds, accountIds) {
    const formData = new FormData();
    formData.append('csrf_token', getCsrfToken());
    for (const stockId of stockIds) {
        for (const accountId of accountIds) {
            formData.append('pairs[]', JSON.stringify({ 'stock_id': stockId, 'account_id': accountId }));
        }
    }

    const response = await fetch(`${BASE_PATH}/api/assets/daily_assets_by_stock_account_pairs`, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin', // セッション / CSRF用
    });

    if (!response.ok) {
        console.error('検索に失敗しました');
        return;
    }

    const json = await response.json();
    const dailyAssetsTotal = json.dailyAssetsTotal;
    const latestAssetDetail = json.latestAssetDetail;
    const expectedDividends = json.expectedDividends;

    return [dailyAssetsTotal, latestAssetDetail, expectedDividends];

}

export async function getExpectDividends(stockIds) {

}