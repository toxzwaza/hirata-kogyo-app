<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    workRates: Object,
    drawings: Array,
    workMethods: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const drawingId = ref(props.filters?.drawing_id || null);
const workMethodId = ref(props.filters?.work_method_id || null);

// 再紐づけモーダル
const showRelinkModal = ref(false);
const relinkLoading = ref(false);
const relinkResult = ref(null);
const relinkError = ref(null);

const runRelink = () => {
    if (!confirm('無効な単価に紐づく作業実績を、有効な単価に再紐づけします。よろしいですか？')) return;
    relinkLoading.value = true;
    relinkError.value = null;
    relinkResult.value = null;
    showRelinkModal.value = true;
    window.axios.post(route('work-rates.relink-work-records'))
        .then(({ data }) => {
            relinkResult.value = data;
            router.reload({ only: ['workRates'] });
        })
        .catch((err) => {
            relinkError.value = err.response?.data?.message || err.message || '実行中にエラーが発生しました。';
        })
        .finally(() => {
            relinkLoading.value = false;
        });
};

const closeRelinkModal = () => {
    showRelinkModal.value = false;
    relinkResult.value = null;
    relinkError.value = null;
};

// フィルターが変更されたときに再初期化
watch(() => props.filters, (newFilters) => {
    if (newFilters) {
        search.value = newFilters.search || '';
        drawingId.value = newFilters.drawing_id || null;
        workMethodId.value = newFilters.work_method_id || null;
    }
}, { deep: true });

const applyFilters = () => {
    router.get(route('work-rates.index'), {
        search: search.value,
        drawing_id: drawingId.value,
        work_method_id: workMethodId.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    search.value = '';
    drawingId.value = null;
    workMethodId.value = null;
    router.get(route('work-rates.index'));
};

const formatDate = (date) => {
    if (!date) return '無期限';
    return new Date(date).toLocaleDateString('ja-JP');
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('ja-JP', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
};

// 適用状況の使用テキスト（作業実績・請求書での使用状況）
const applicationStatusText = (rate) => {
    const n = rate.work_records_count ?? 0;
    const invoiced = rate.work_records_invoiced_count ?? 0;
    if (n === 0) return '';
    if (invoiced === 0) return `使用中（実績 ${n} 件）`;
    return `使用中（実績 ${n} 件・請求書反映 ${invoiced} 件）`;
};
</script>

<template>
    <Head title="作業単価管理" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">作業単価管理</h2>
                <div class="flex gap-2">
                    <button
                        type="button"
                        @click="runRelink"
                        :disabled="relinkLoading"
                        class="bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white font-bold py-2 px-4 rounded"
                    >
                        {{ relinkLoading ? '実行中...' : '再紐づけ' }}
                    </button>
                    <Link
                        :href="route('work-rates.create')"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                    >
                        新規登録
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- 検索 -->
                <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="図番または品名で検索"
                                class="w-full rounded-md border-gray-300 shadow-sm"
                                @keyup.enter="applyFilters"
                            />
                        </div>
                        <div>
                            <select
                                v-model="drawingId"
                                class="w-full rounded-md border-gray-300 shadow-sm"
                            >
                                <option :value="null">すべての図番</option>
                                <option
                                    v-for="drawing in drawings"
                                    :key="drawing.id"
                                    :value="drawing.id"
                                >
                                    {{ drawing.drawing_number }} ({{ drawing.client.name }})
                                </option>
                            </select>
                        </div>
                        <div>
                            <select
                                v-model="workMethodId"
                                class="w-full rounded-md border-gray-300 shadow-sm"
                            >
                                <option :value="null">すべての作業方法</option>
                                <option
                                    v-for="method in workMethods"
                                    :key="method.id"
                                    :value="method.id"
                                >
                                    {{ method.name }}
                                </option>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button
                                @click="applyFilters"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                            >
                                検索
                            </button>
                            <button
                                @click="clearFilters"
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                            >
                                クリア
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 一覧テーブル -->
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    図番
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    品名
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    作業方法
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    客先単価
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    スタッフ単価
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    スタッフ残業単価
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    適用期間
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    適用
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    適用状況
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    操作
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="rate in workRates.data" :key="rate.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ rate.drawing.drawing_number }}<br>
                                    <span class="text-xs text-gray-500">{{ rate.drawing.client.name }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ rate.drawing.product_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ rate.work_method.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ¥{{ formatNumber(rate.rate_employee) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ¥{{ formatNumber(rate.rate_contractor) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ¥{{ formatNumber(rate.rate_overtime) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatDate(rate.effective_from) }} ～ {{ formatDate(rate.effective_to) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="[
                                            'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                                            rate.active_flg !== false
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-gray-100 text-gray-500',
                                        ]"
                                    >
                                        {{ rate.active_flg !== false ? '有効' : '無効' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <span v-if="applicationStatusText(rate)" class="text-gray-600">
                                        {{ applicationStatusText(rate) }}
                                    </span>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <Link
                                        :href="route('work-rates.edit', rate.id)"
                                        class="text-blue-600 hover:text-blue-900"
                                    >
                                        編集
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- ページネーション -->
                    <div class="px-6 py-4 border-t border-gray-200" v-if="workRates.links">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                全 {{ workRates.total }} 件中 {{ workRates.from }} - {{ workRates.to }} 件を表示
                            </div>
                            <div class="flex gap-2">
                                <Link
                                    v-for="link in workRates.links"
                                    :key="link.label"
                                    :href="link.url || '#'"
                                    :class="[
                                        'px-3 py-2 rounded-md text-sm',
                                        link.active ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700',
                                        !link.url ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-300'
                                    ]"
                                    v-html="link.label"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 再紐づけ結果モーダル -->
        <Teleport to="body">
            <div
                v-if="showRelinkModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
                @click.self="closeRelinkModal"
            >
                <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] flex flex-col">
                    <div class="px-6 py-4 border-b border-gray-200 font-semibold text-gray-800">
                        無効→有効 再紐づけ 実行結果
                    </div>
                    <div class="p-6 overflow-y-auto flex-1">
                        <div v-if="relinkLoading" class="text-center py-8 text-gray-600">
                            実行中...
                        </div>
                        <div v-else-if="relinkError" class="text-red-600">
                            {{ relinkError }}
                        </div>
                        <template v-else-if="relinkResult">
                            <div class="space-y-2 mb-4">
                                <p><span class="font-medium">対象:</span> {{ relinkResult.total }} 件</p>
                                <p><span class="font-medium">再紐づけ:</span> {{ relinkResult.updated }} 件</p>
                                <p v-if="relinkResult.skipped > 0"><span class="font-medium">スキップ:</span> {{ relinkResult.skipped }} 件</p>
                            </div>
                            <div v-if="relinkResult.details && relinkResult.details.length" class="mt-4">
                                <p class="font-medium text-gray-700 mb-2">詳細:</p>
                                <ul class="list-disc list-inside text-sm text-gray-600 space-y-1 max-h-60 overflow-y-auto">
                                    <li v-for="(line, idx) in relinkResult.details" :key="idx">{{ line }}</li>
                                </ul>
                            </div>
                        </template>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                        <button
                            type="button"
                            @click="closeRelinkModal"
                            :disabled="relinkLoading"
                            class="bg-gray-500 hover:bg-gray-600 disabled:opacity-50 text-white font-bold py-2 px-4 rounded"
                        >
                            閉じる
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>








