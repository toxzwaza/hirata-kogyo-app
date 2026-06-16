<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import axios from 'axios';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    rows: { type: Array, required: true },
    targetHourly: { type: Number, required: true },
    period: { type: Object, default: () => ({ from: '', to: '' }) },
});

const sortKey = ref('effective_hourly');
const sortAsc = ref(true);

/* ---------- 明細モーダル ---------- */
const modalShow = ref(false);
const modalLoading = ref(false);
const modalError = ref('');
const modalRow = ref(null); // クリックした採算行
const modalRecords = ref([]);

const openDetail = async (row) => {
    modalRow.value = row;
    modalShow.value = true;
    modalRecords.value = [];
    modalError.value = '';
    modalLoading.value = true;
    try {
        const { data } = await axios.get(route('dashboard.work-records'), {
            params: {
                drawing_id: row.drawing_id,
                work_method_id: row.work_method_id,
                from: props.period.from,
                to: props.period.to,
            },
        });
        modalRecords.value = data.records;
    } catch (e) {
        modalError.value = '明細の取得に失敗しました。';
    } finally {
        modalLoading.value = false;
    }
};

const closeDetail = () => {
    modalShow.value = false;
};

// 作業実績の編集画面へ遷移
const goToEdit = (rec) => {
    router.visit(route('work-records.edit', rec.id));
};

// 1個あたりの平均作業時間（分）
const avgMinutesPerPiece = computed(() => {
    const r = modalRow.value;
    if (!r || !r.total_quantity) return null;
    return r.total_minutes / r.total_quantity;
});

const hm = (minutes) => {
    if (minutes === null || minutes === undefined) return '—';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return h > 0 ? `${h}時間${m}分` : `${m}分`;
};

// 各行に「必要単価」「値上げ要求額」を目標時給から算出して付与
const enriched = computed(() =>
    props.rows.map((r) => {
        // 必要単価(円/kg) = 目標時給 ÷ 生産性(kg/h)
        const requiredRate =
            r.kg_per_hour > 0 ? props.targetHourly / r.kg_per_hour : null;
        // 値上げ要求額 = 必要単価 − 現行単価
        const rateGap =
            requiredRate !== null && r.current_rate !== null
                ? requiredRate - r.current_rate
                : null;
        // 現行の個単価(円/個) = round(1個重量 × kg単価)
        const currentPiecePrice =
            r.current_rate !== null && r.weight_per_unit
                ? Math.round(r.weight_per_unit * r.current_rate)
                : null;
        return {
            ...r,
            required_rate: requiredRate,
            rate_gap: rateGap,
            current_piece_price: currentPiecePrice,
            below_target: r.effective_hourly < props.targetHourly,
        };
    })
);

const sorted = computed(() => {
    const arr = [...enriched.value];
    arr.sort((a, b) => {
        const av = a[sortKey.value];
        const bv = b[sortKey.value];
        // null は常に末尾
        if (av === null) return 1;
        if (bv === null) return -1;
        if (typeof av === 'string') {
            return sortAsc.value ? av.localeCompare(bv) : bv.localeCompare(av);
        }
        return sortAsc.value ? av - bv : bv - av;
    });
    return arr;
});

const setSort = (key) => {
    if (sortKey.value === key) {
        sortAsc.value = !sortAsc.value;
    } else {
        sortKey.value = key;
        sortAsc.value = true;
    }
};

const yen = (v) =>
    v === null || v === undefined ? '—' : '¥' + Math.round(v).toLocaleString();
const num = (v, digits = 1) =>
    v === null || v === undefined ? '—' : Number(v).toFixed(digits);

const columns = [
    { key: 'drawing_number', label: '図番', align: 'left' },
    { key: 'record_count', label: '件数', align: 'right' },
    { key: 'kg_per_hour', label: '生産性(kg/h)', align: 'right' },
    { key: 'effective_hourly', label: '実効時給', align: 'right' },
    { key: 'current_rate', label: '現行単価', align: 'right' },
    { key: 'required_rate', label: '必要単価', align: 'right' },
    { key: 'rate_gap', label: '値上げ要求額', align: 'right' },
];
</script>

<template>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b bg-gray-50 text-gray-600">
                    <th
                        v-for="col in columns"
                        :key="col.key"
                        class="px-3 py-2 font-medium cursor-pointer select-none whitespace-nowrap"
                        :class="col.align === 'right' ? 'text-right' : 'text-left'"
                        @click="setSort(col.key)"
                    >
                        {{ col.label }}
                        <span v-if="sortKey === col.key">{{ sortAsc ? '▲' : '▼' }}</span>
                    </th>
                    <th class="px-3 py-2 text-center font-medium whitespace-nowrap">操作</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="row in sorted"
                    :key="`${row.drawing_id}-${row.work_method_id}`"
                    class="border-b cursor-pointer"
                    :class="row.below_target ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-gray-50'"
                    @click="openDetail(row)"
                >
                    <td class="px-3 py-2">
                        <div class="font-medium text-gray-900 flex items-center gap-1">
                            {{ row.drawing_number }}
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ row.product_name }} / {{ row.client_name }}
                        </div>
                    </td>
                    <td class="px-3 py-2 text-right">{{ row.record_count }}</td>
                    <td class="px-3 py-2 text-right">{{ num(row.kg_per_hour, 2) }}</td>
                    <td
                        class="px-3 py-2 text-right font-semibold"
                        :class="row.below_target ? 'text-red-600' : 'text-gray-900'"
                    >
                        {{ yen(row.effective_hourly) }}
                    </td>
                    <td class="px-3 py-2 text-right">
                        <template v-if="row.current_rate === null">未設定</template>
                        <template v-else>
                            <div>{{ yen(row.current_rate) }}/kg</div>
                            <div v-if="row.current_piece_price !== null" class="text-xs text-gray-500">
                                {{ yen(row.current_piece_price) }}/個
                            </div>
                        </template>
                    </td>
                    <td class="px-3 py-2 text-right">
                        {{ row.required_rate === null ? '—' : yen(row.required_rate) + '/kg' }}
                    </td>
                    <td
                        class="px-3 py-2 text-right font-medium"
                        :class="
                            row.rate_gap === null
                                ? 'text-gray-400'
                                : row.rate_gap > 0
                                ? 'text-red-600'
                                : 'text-green-600'
                        "
                    >
                        <span v-if="row.rate_gap === null">—</span>
                        <span v-else>{{ (row.rate_gap > 0 ? '+' : '') + yen(row.rate_gap) }}/kg</span>
                    </td>
                    <td class="px-3 py-2 text-center whitespace-nowrap" @click.stop>
                        <Link
                            :href="
                                route('work-rates.index', {
                                    drawing_id: row.drawing_id,
                                    work_method_id: row.work_method_id,
                                })
                            "
                            class="text-blue-600 hover:underline"
                        >
                            単価編集
                        </Link>
                    </td>
                </tr>
                <tr v-if="sorted.length === 0">
                    <td :colspan="columns.length + 1" class="px-3 py-6 text-center text-gray-400">
                        対象期間の作業実績がありません。
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- 作業実績明細モーダル -->
    <Modal :show="modalShow" max-width="5xl" @close="closeDetail">
        <div class="p-6">
            <div class="flex items-start justify-between mb-4">
                <div v-if="modalRow">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ modalRow.drawing_number }}
                        <span class="text-sm font-normal text-gray-500">／ {{ modalRow.work_method_name }}</span>
                    </h3>
                    <p class="text-sm text-gray-500">
                        {{ modalRow.product_name }} / {{ modalRow.client_name }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                        集計期間 {{ period.from }} 〜 {{ period.to }}
                    </p>
                </div>
                <button @click="closeDetail" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <!-- サマリ -->
            <div v-if="modalRow" class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-4">
                <div class="bg-gray-50 rounded p-2">
                    <p class="text-xs text-gray-500">実効時給</p>
                    <p class="font-semibold" :class="modalRow.below_target ? 'text-red-600' : 'text-gray-900'">{{ yen(modalRow.effective_hourly) }}/h</p>
                </div>
                <div class="bg-gray-50 rounded p-2">
                    <p class="text-xs text-gray-500">生産性</p>
                    <p class="font-semibold text-gray-900">{{ num(modalRow.kg_per_hour, 2) }} kg/h</p>
                </div>
                <div class="bg-gray-50 rounded p-2">
                    <p class="text-xs text-gray-500">平均作業時間/個</p>
                    <p class="font-semibold text-gray-900">{{ num(avgMinutesPerPiece, 1) }} 分/個</p>
                </div>
                <div class="bg-gray-50 rounded p-2">
                    <p class="text-xs text-gray-500">総作業時間</p>
                    <p class="font-semibold text-gray-900">{{ hm(modalRow.total_minutes) }}</p>
                </div>
                <div class="bg-gray-50 rounded p-2">
                    <p class="text-xs text-gray-500">総生産数 / 重量</p>
                    <p class="font-semibold text-gray-900">{{ modalRow.total_quantity }}個 / {{ modalRow.total_weight }}kg</p>
                </div>
            </div>

            <div v-if="modalLoading" class="py-10 text-center text-gray-400">読み込み中…</div>
            <div v-else-if="modalError" class="py-10 text-center text-red-500">{{ modalError }}</div>
            <div v-else class="overflow-x-auto max-h-[50vh] overflow-y-auto">
                <table class="min-w-full text-sm">
                    <thead class="sticky top-0 bg-gray-100 text-gray-600">
                        <tr class="border-b">
                            <th class="px-3 py-2 text-left font-medium whitespace-nowrap">スタッフ</th>
                            <th class="px-3 py-2 text-left font-medium whitespace-nowrap">日付</th>
                            <th class="px-3 py-2 text-left font-medium whitespace-nowrap">時間帯</th>
                            <th class="px-3 py-2 text-right font-medium whitespace-nowrap">作業時間</th>
                            <th class="px-3 py-2 text-right font-medium whitespace-nowrap">分/個</th>
                            <th class="px-3 py-2 text-right font-medium whitespace-nowrap">良品</th>
                            <th class="px-3 py-2 text-right font-medium whitespace-nowrap">不良</th>
                            <th class="px-3 py-2 text-right font-medium whitespace-nowrap">重量(kg)</th>
                            <th class="px-3 py-2 text-right font-medium whitespace-nowrap">想定金額</th>
                            <th class="px-3 py-2 text-left font-medium whitespace-nowrap">備考</th>
                            <th class="px-3 py-2 text-center font-medium whitespace-nowrap"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="rec in modalRecords"
                            :key="rec.id"
                            class="border-b hover:bg-blue-50 cursor-pointer"
                            @click="goToEdit(rec)"
                        >
                            <td class="px-3 py-2 whitespace-nowrap">{{ rec.staff_name }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ rec.date }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ rec.start }}〜{{ rec.end }}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">{{ hm(rec.work_minutes) }}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                {{ rec.total_quantity ? num(rec.work_minutes / rec.total_quantity, 1) : '—' }}
                            </td>
                            <td class="px-3 py-2 text-right">{{ rec.quantity_good }}</td>
                            <td class="px-3 py-2 text-right">{{ rec.quantity_ng }}</td>
                            <td class="px-3 py-2 text-right">{{ rec.weight }}</td>
                            <td class="px-3 py-2 text-right">{{ yen(rec.amount) }}</td>
                            <td class="px-3 py-2 text-gray-500 max-w-[12rem] truncate" :title="rec.memo">{{ rec.memo }}</td>
                            <td class="px-3 py-2 text-center whitespace-nowrap text-blue-600">編集 ›</td>
                        </tr>
                        <tr v-if="modalRecords.length === 0">
                            <td colspan="11" class="px-3 py-6 text-center text-gray-400">明細がありません。</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-5 flex justify-end">
                <SecondaryButton @click="closeDetail">閉じる</SecondaryButton>
            </div>
        </div>
    </Modal>
</template>
