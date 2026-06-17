<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import NegotiationTable from '@/Components/dashboard/NegotiationTable.vue';
import BarChart from '@/Components/dashboard/BarChart.vue';
import LineChart from '@/Components/dashboard/LineChart.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    summary: { type: Object, default: () => ({}) },
    manualWorkRecordCount: { type: Number, default: 0 },
    rateNegotiation: { type: Array, default: () => [] },
    staffProductivity: { type: Array, default: () => [] },
    monthlyTrend: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

/* ---------- 期間セレクタ（21日締め：21日〜翌月20日） ---------- */
// 月ピッカーは「締め月」を表す。締め月 M = (M-1)月21日 〜 M月20日。
// filters.to は締め月の20日なので、その YYYY-MM を締め月ラベルとして使う。
const selectedMonth = ref((props.filters.to || '').slice(0, 7));

const reload = (from, to) => {
    router.get(
        route('dashboard'),
        { from, to },
        { preserveScroll: true, preserveState: false }
    );
};

const fmt = (d) =>
    `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(
        d.getDate()
    ).padStart(2, '0')}`;

// 締め月(y, m: 1-12) → 集計期間 { from:(m-1)月21日, to:m月20日 }
const cycleOfClosingMonth = (y, m) => ({
    from: fmt(new Date(y, m - 2, 21)), // m-2: 0-indexedの前月
    to: fmt(new Date(y, m - 1, 20)),
});

// 今日を含む締めサイクルの「締め月」
const currentClosingMonth = () => {
    const now = new Date();
    if (now.getDate() >= 21) {
        // 当月21日〜翌月20日 → 締め月は翌月
        const d = new Date(now.getFullYear(), now.getMonth() + 1, 1);
        return { y: d.getFullYear(), m: d.getMonth() + 1 };
    }
    // 前月21日〜当月20日 → 締め月は当月
    return { y: now.getFullYear(), m: now.getMonth() + 1 };
};

const applyMonth = () => {
    if (!selectedMonth.value) return;
    const [y, m] = selectedMonth.value.split('-').map(Number);
    const c = cycleOfClosingMonth(y, m);
    reload(c.from, c.to);
};

const presetThisMonth = () => {
    const c = currentClosingMonth();
    const cycle = cycleOfClosingMonth(c.y, c.m);
    reload(cycle.from, cycle.to);
};
const presetLastMonth = () => {
    const c = currentClosingMonth();
    const d = new Date(c.y, c.m - 2, 1); // 締め月の1つ前
    const cycle = cycleOfClosingMonth(d.getFullYear(), d.getMonth() + 1);
    reload(cycle.from, cycle.to);
};
// 直近 n 締めサイクル分（n-1サイクル前の開始 〜 今サイクルの終了）
const presetRange = (months) => {
    const c = currentClosingMonth();
    const end = cycleOfClosingMonth(c.y, c.m);
    const startClosing = new Date(c.y, c.m - 1 - (months - 1), 1);
    const start = cycleOfClosingMonth(
        startClosing.getFullYear(),
        startClosing.getMonth() + 1
    );
    reload(start.from, end.to);
};

/* ---------- 目標時給（単価交渉の基準） ---------- */
const TARGET_KEY = 'hirata_dashboard_target_hourly';
const targetHourly = ref(
    Number(localStorage.getItem(TARGET_KEY)) || 3000
);
const onTargetChange = () => {
    if (!targetHourly.value || targetHourly.value < 0) targetHourly.value = 0;
    localStorage.setItem(TARGET_KEY, String(targetHourly.value));
};

/* ---------- 表示ヘルパ ---------- */
const yen = (v) => '¥' + Math.round(v || 0).toLocaleString();

const periodLabel = computed(
    () => `${props.filters.from || ''} 〜 ${props.filters.to || ''}`
);

const belowTargetCount = computed(
    () => props.rateNegotiation.filter((r) => r.effective_hourly < targetHourly.value).length
);

/* ---------- スタッフ別生産性グラフ ---------- */
const staffChart = computed(() => ({
    labels: props.staffProductivity.map((s) => s.staff_name),
    datasets: [
        {
            label: '生産性 (kg/h)',
            data: props.staffProductivity.map((s) => s.kg_per_hour ?? 0),
            backgroundColor: '#3b82f6',
        },
    ],
}));

/* ---------- 月次推移グラフ ---------- */
const trendChart = computed(() => ({
    labels: props.monthlyTrend.map((m) => m.month),
    datasets: [
        {
            label: '想定売上 (円)',
            data: props.monthlyTrend.map((m) => m.revenue),
            borderColor: '#16a34a',
            backgroundColor: '#16a34a',
            yAxisID: 'y',
            tension: 0.3,
        },
        {
            label: '生産重量 (kg)',
            data: props.monthlyTrend.map((m) => m.total_weight),
            borderColor: '#a855f7',
            backgroundColor: '#a855f7',
            yAxisID: 'y1',
            tension: 0.3,
        },
    ],
}));
</script>

<template>
    <Head title="ダッシュボード" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">ダッシュボード</h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- 期間セレクタ＋目標時給 -->
                <div class="bg-white shadow-sm sm:rounded-lg p-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm text-gray-500">集計期間</span>
                        <span class="text-xs text-gray-400 border border-gray-200 rounded px-1.5 py-0.5">21日締め</span>
                        <input
                            type="month"
                            v-model="selectedMonth"
                            @change="applyMonth"
                            class="border-gray-300 rounded-md text-sm"
                            title="締め月を選択（締め月の前月21日〜当月20日）"
                        />
                        <span class="text-xs text-gray-400">{{ periodLabel }}</span>
                        <div class="flex gap-1 ml-2">
                            <button @click="presetThisMonth" class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200">今締め</button>
                            <button @click="presetLastMonth" class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200">前締め</button>
                            <button @click="presetRange(3)" class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200">直近3ヶ月</button>
                            <button @click="presetRange(6)" class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200">直近6ヶ月</button>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-500">目標時給</label>
                        <div class="relative">
                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-sm">¥</span>
                            <input
                                type="number"
                                step="100"
                                min="0"
                                v-model.number="targetHourly"
                                @change="onTargetChange"
                                class="border-gray-300 rounded-md text-sm pl-6 w-32"
                            />
                        </div>
                        <span class="text-sm text-gray-400">/h</span>
                    </div>
                </div>

                <!-- KPIカード -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <StatCard
                        label="客先請求予定額（期間内・下書き＋確定）"
                        :value="yen(summary.client_invoice_total)"
                        accent="purple"
                    />
                    <StatCard
                        label="スタッフ支払予定額（期間内・下書き＋確定）"
                        :value="yen(summary.staff_invoice_total)"
                        accent="green"
                    />
                    <StatCard
                        label="目標時給未達の図番数"
                        :value="String(belowTargetCount) + ' 件'"
                        :sub="`目標 ${yen(targetHourly)}/h 未満`"
                        accent="amber"
                    />

                    <!-- 手動登録件数（クリックで紐づけ画面へ） -->
                    <Link
                        :href="route('work-records.manual.index')"
                        class="block bg-white overflow-hidden shadow-sm sm:rounded-lg transition hover:shadow-md hover:ring-2 hover:ring-rose-200"
                    >
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 w-2 h-10 rounded" :class="manualWorkRecordCount > 0 ? 'bg-rose-500' : 'bg-gray-300'"></div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm text-gray-500">手動登録件数（図番未紐づけ）</p>
                                    <p class="text-2xl font-bold" :class="manualWorkRecordCount > 0 ? 'text-rose-600' : 'text-gray-900'">
                                        {{ manualWorkRecordCount }} 件
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ manualWorkRecordCount > 0 ? 'クリックして図番・単価を紐づけ' : '未処理なし' }}
                                    </p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
                                </svg>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- 単価交渉支援（採算分析） -->
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-4 border-b flex items-center justify-between flex-wrap gap-2">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">単価交渉支援（採算分析）</h3>
                            <p class="text-xs text-gray-500 mt-1">
                                図番×作業方法ごとの実効時給（想定売上÷作業時間）。
                                <span class="text-red-600">赤字</span>＝目標時給未達＝値上げ交渉の候補。
                            </p>
                        </div>
                        <p class="text-xs text-gray-400">
                            ※ 作業実績からの想定値です。確定済請求書の金額とは独立した参考指標です。
                        </p>
                    </div>
                    <div class="p-4">
                        <NegotiationTable :rows="rateNegotiation" :target-hourly="targetHourly" :period="filters" />
                    </div>
                </div>

                <!-- スタッフ別生産性／月次推移 -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">スタッフ別生産性</h3>
                        <BarChart
                            v-if="staffProductivity.length"
                            :labels="staffChart.labels"
                            :datasets="staffChart.datasets"
                        />
                        <p v-else class="text-sm text-gray-400 py-8 text-center">対象期間のデータがありません。</p>
                        <table v-if="staffProductivity.length" class="min-w-full text-sm mt-4">
                            <thead>
                                <tr class="border-b bg-gray-50 text-gray-600">
                                    <th class="px-3 py-2 text-left font-medium">スタッフ</th>
                                    <th class="px-3 py-2 text-right font-medium">生産重量(kg)</th>
                                    <th class="px-3 py-2 text-right font-medium">kg/h</th>
                                    <th class="px-3 py-2 text-right font-medium">実効時給</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="s in staffProductivity" :key="s.staff_id" class="border-b">
                                    <td class="px-3 py-2">{{ s.staff_name }}</td>
                                    <td class="px-3 py-2 text-right">{{ Number(s.total_weight).toLocaleString() }}</td>
                                    <td class="px-3 py-2 text-right">{{ s.kg_per_hour ?? '—' }}</td>
                                    <td class="px-3 py-2 text-right">{{ s.effective_hourly === null ? '—' : yen(s.effective_hourly) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">月次推移（直近6ヶ月）</h3>
                        <LineChart
                            :labels="trendChart.labels"
                            :datasets="trendChart.datasets"
                            :dual-axis="true"
                        />
                    </div>
                </div>

                <!-- クイックリンク -->
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-gray-500 mb-3">クイックリンク</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
                        <Link :href="route('work-records.index')" class="px-3 py-2 text-sm rounded bg-gray-50 hover:bg-gray-100 text-gray-700 text-center">作業実績</Link>
                        <Link :href="route('staff-invoices.index')" class="px-3 py-2 text-sm rounded bg-gray-50 hover:bg-gray-100 text-gray-700 text-center">スタッフ請求書</Link>
                        <Link :href="route('client-invoices.index')" class="px-3 py-2 text-sm rounded bg-gray-50 hover:bg-gray-100 text-gray-700 text-center">客先請求書</Link>
                        <Link :href="route('drawings.index')" class="px-3 py-2 text-sm rounded bg-gray-50 hover:bg-gray-100 text-gray-700 text-center">図番管理</Link>
                        <Link :href="route('work-rates.index')" class="px-3 py-2 text-sm rounded bg-gray-50 hover:bg-gray-100 text-gray-700 text-center">作業単価管理</Link>
                        <Link :href="route('staff.index')" class="px-3 py-2 text-sm rounded bg-gray-50 hover:bg-gray-100 text-gray-700 text-center">スタッフ管理</Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
