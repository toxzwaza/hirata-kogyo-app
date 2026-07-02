/**
 * 一覧の絞り込み保持ヘルパー
 *
 * 編集画面のURLクエリ（＝一覧から引き継いだ絞り込み条件）を、
 * back[...] という名前空間付きのクエリ文字列に変換して返す。
 * 更新・削除リクエストの送信先URLに付与することで、
 * サーバー側が同じ絞り込みで一覧へリダイレクトできる。
 *
 * back[...] で名前空間を分けるのは、フォーム本文の staff_id 等の
 * フィールド名と衝突させないため。
 *
 * @returns {string} 例: "?back[client_id]=3&back[search]=abc"（無ければ ""）
 */
export function buildBackSuffix() {
    const current = new URLSearchParams(window.location.search);
    const params = new URLSearchParams();
    for (const [key, value] of current.entries()) {
        if (value !== null && value !== '') {
            params.append(`back[${key}]`, value);
        }
    }
    const qs = params.toString();
    return qs ? `?${qs}` : '';
}
