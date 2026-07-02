<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * 一覧の絞り込み保持トレイト
 *
 * 編集画面から back[...] として引き継いだ一覧の絞り込み条件を取り出す。
 * 更新・削除後に redirect()->route('xxx.index', $this->backFilters($request)) と
 * 渡すことで、同じ絞り込み状態のまま一覧へ戻せる。
 */
trait PreservesListFilters
{
    /**
     * back[...] で送られた絞り込み条件（空値を除く）を配列で返す。
     */
    protected function backFilters(Request $request): array
    {
        $back = $request->input('back', []);
        if (!is_array($back)) {
            return [];
        }

        return collect($back)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();
    }
}
