<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    records: { type: Array, default: () => [] },
    drawings: { type: Array, default: () => [] },
    workMethods: { type: Array, default: () => [] },
    clients: { type: Array, default: () => [] },
});

// ===== 選択状態（手動グルーピング） =====
const selectedIds = ref([]);

const isSelected = (id) => selectedIds.value.includes(id);

const toggle = (id) => {
    if (isSelected(id)) {
        selectedIds.value = selectedIds.value.filter((x) => x !== id);
    } else {
        selectedIds.value = [...selectedIds.value, id];
    }
};

const allSelected = computed(() =>
    props.records.length > 0 && selectedIds.value.length === props.records.length
);

const toggleAll = () => {
    selectedIds.value = allSelected.value ? [] : props.records.map((r) => r.id);
};

const clearSelection = () => {
    selectedIds.value = [];
};

const selectedRecords = computed(() =>
    props.records.filter((r) => selectedIds.value.includes(r.id))
);

// 選択レコードに含まれる作業方法（重複なし）
const distinctMethods = computed(() => {
    const map = new Map();
    selectedRecords.value.forEach((r) => {
        if (!map.has(r.work_method_id)) {
            map.set(r.work_method_id, r.work_method_name);
        }
    });
    return Array.from(map.entries()).map(([id, name]) => ({ id, name }));
});

// 選択レコードの最も早い作業日（適用開始日の初期値に使う）
const earliestDate = computed(() => {
    const dates = selectedRecords.value
        .map((r) => (r.start_time || '').slice(0, 10))
        .filter(Boolean)
        .sort();
    return dates.length ? dates[0] : '';
});

// ===== 既存図番へ紐づけ =====
const showLinkModal = ref(false);
const drawingNumberInput = ref('');

const matchedDrawing = computed(() =>
    props.drawings.find((d) => d.drawing_number === drawingNumberInput.value) || null
);

const linkForm = useForm({
    work_record_ids: [],
    drawing_id: null,
});

const openLinkModal = () => {
    drawingNumberInput.value = '';
    linkForm.clearErrors();
    showLinkModal.value = true;
};

const submitLink = () => {
    if (!matchedDrawing.value) return;
    linkForm.work_record_ids = [...selectedIds.value];
    linkForm.drawing_id = matchedDrawing.value.id;
    linkForm.post(route('work-records.manual.link-existing'), {
        preserveScroll: true,
        onSuccess: () => {
            showLinkModal.value = false;
            selectedIds.value = [];
        },
    });
};

// ===== 新規図番を登録して紐づけ =====
const showCreateModal = ref(false);

const createForm = useForm({
    work_record_ids: [],
    drawing_number: '',
    product_name: '',
    client_id: null,
    weight_per_unit: null,
    effective_from: '',
    rates: [],
});

const openCreateModal = () => {
    createForm.clearErrors();
    const first = selectedRecords.value[0] || {};
    createForm.drawing_number = first.manual_drawing_number || '';
    createForm.product_name = first.manual_product_name || '';
    // 手動入力の得意先名が登録済み得意先と一致すれば初期選択
    const matchClient = props.clients.find((c) => c.name === first.manual_client_name);
    createForm.client_id = matchClient ? matchClient.id : null;
    createForm.weight_per_unit = null;
    createForm.effective_from = earliestDate.value;
    // 選択レコードの作業方法ごとに単価入力欄を用意
    createForm.rates = distinctMethods.value.map((m) => ({
        work_method_id: m.id,
        work_method_name: m.name,
        rate_employee: null,
        rate_contractor: null,
        rate_overtime: null,
    }));
    showCreateModal.value = true;
};

const submitCreate = () => {
    createForm.work_record_ids = [...selectedIds.value];
    createForm.post(route('work-records.manual.create-and-link'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            selectedIds.value = [];
        },
    });
};
</script>

<template>
    <Head title="手動登録の紐づけ" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">手動登録の紐づけ</h2>
                <Link :href="route('dashboard')" class="text-sm text-blue-600 hover:text-blue-900">
                    ← ダッシュボードへ
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- フラッシュ -->
                <div v-if="$page.props.flash?.success" class="p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="linkForm.errors.error || createForm.errors.error" class="p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    {{ linkForm.errors.error || createForm.errors.error }}
                </div>

                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-sm text-gray-600">
                        スタッフが手動入力した未登録図番の実績です。同じ品物と判断できるものにチェックを入れてグルーピングし、
                        まとめて「既存図番に紐づける」または「新規図番を登録して紐づける」で処理してください。
                        （同じ品番でも入力表記が異なる場合があります）
                    </p>
                </div>

                <!-- 一覧 -->
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <div v-if="records.length === 0" class="p-10 text-center text-gray-500">
                        未処理の手動登録はありません。
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left">
                                        <input type="checkbox" :checked="allSelected" @change="toggleAll" class="w-5 h-5 rounded border-gray-300" />
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">品番（手動）</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">品名（手動）</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">得意先（手動）</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">作業方法</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">スタッフ</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">作業日時</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">良品数</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="rec in records"
                                    :key="rec.id"
                                    :class="isSelected(rec.id) ? 'bg-blue-50' : 'hover:bg-gray-50'"
                                    class="cursor-pointer"
                                    @click="toggle(rec.id)"
                                >
                                    <td class="px-4 py-3" @click.stop>
                                        <input type="checkbox" :checked="isSelected(rec.id)" @change="toggle(rec.id)" class="w-5 h-5 rounded border-gray-300" />
                                    </td>
                                    <td class="px-4 py-3 text-sm font-mono font-semibold text-gray-900">{{ rec.manual_drawing_number || '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ rec.manual_product_name || '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ rec.manual_client_name || '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ rec.work_method_name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ rec.staff_name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ rec.start_time }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ rec.quantity_good }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 選択アクションバー -->
        <div
            v-if="selectedIds.length > 0"
            class="fixed bottom-0 inset-x-0 bg-white border-t-2 border-gray-200 shadow-2xl z-40"
        >
            <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-3">
                    <span class="text-lg font-bold text-gray-800">{{ selectedIds.length }} 件選択中</span>
                    <button type="button" @click="clearSelection" class="text-sm text-gray-500 hover:text-gray-800 underline">
                        選択解除
                    </button>
                </div>
                <div class="flex gap-2">
                    <button
                        type="button"
                        @click="openLinkModal"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-5 rounded-lg shadow"
                    >
                        既存図番に紐づける
                    </button>
                    <button
                        type="button"
                        @click="openCreateModal"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-5 rounded-lg shadow"
                    >
                        新規図番を登録して紐づける
                    </button>
                </div>
            </div>
        </div>

        <!-- 既存図番紐づけモーダル -->
        <div v-if="showLinkModal" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" @click.self="showLinkModal = false">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">既存図番に紐づける（{{ selectedIds.length }}件）</h3>
                <label class="block text-sm font-semibold text-gray-700 mb-2">図番を選択</label>
                <input
                    v-model="drawingNumberInput"
                    type="text"
                    list="manual-link-drawing-list"
                    placeholder="図番を入力して選択してください"
                    class="w-full h-12 rounded-lg border-2 border-gray-300 px-3"
                    :class="{ 'border-red-500': linkForm.errors.drawing_id }"
                />
                <datalist id="manual-link-drawing-list">
                    <option v-for="d in drawings" :key="d.id" :value="d.drawing_number">
                        {{ d.drawing_number }} - {{ d.product_name }}
                    </option>
                </datalist>
                <p v-if="matchedDrawing" class="mt-2 text-sm text-green-700">
                    選択中: {{ matchedDrawing.drawing_number }} / {{ matchedDrawing.product_name }}
                </p>
                <p v-else-if="drawingNumberInput" class="mt-2 text-sm text-red-600">
                    一致する図番がありません。一覧から選択してください。
                </p>
                <p v-if="linkForm.errors.drawing_id" class="mt-2 text-sm text-red-600">{{ linkForm.errors.drawing_id }}</p>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="showLinkModal = false" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded-lg">
                        キャンセル
                    </button>
                    <button
                        type="button"
                        @click="submitLink"
                        :disabled="!matchedDrawing || linkForm.processing"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg disabled:opacity-50"
                    >
                        {{ linkForm.processing ? '処理中...' : '紐づける' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- 新規図番登録モーダル -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" @click.self="showCreateModal = false">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">新規図番を登録して紐づける（{{ selectedIds.length }}件）</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">品番 *</label>
                        <input v-model="createForm.drawing_number" type="text" class="w-full h-11 rounded-lg border-2 border-gray-300 px-3" :class="{ 'border-red-500': createForm.errors.drawing_number }" />
                        <p v-if="createForm.errors.drawing_number" class="mt-1 text-xs text-red-600">{{ createForm.errors.drawing_number }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">品名 *</label>
                        <input v-model="createForm.product_name" type="text" class="w-full h-11 rounded-lg border-2 border-gray-300 px-3" :class="{ 'border-red-500': createForm.errors.product_name }" />
                        <p v-if="createForm.errors.product_name" class="mt-1 text-xs text-red-600">{{ createForm.errors.product_name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">得意先 *</label>
                        <select v-model="createForm.client_id" class="w-full h-11 rounded-lg border-2 border-gray-300 px-3" :class="{ 'border-red-500': createForm.errors.client_id }">
                            <option :value="null">選択してください</option>
                            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <p v-if="createForm.errors.client_id" class="mt-1 text-xs text-red-600">{{ createForm.errors.client_id }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">1個あたり重量(kg) *</label>
                        <input v-model.number="createForm.weight_per_unit" type="number" step="0.001" min="0" class="w-full h-11 rounded-lg border-2 border-gray-300 px-3" :class="{ 'border-red-500': createForm.errors.weight_per_unit }" />
                        <p v-if="createForm.errors.weight_per_unit" class="mt-1 text-xs text-red-600">{{ createForm.errors.weight_per_unit }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">単価 適用開始日 *</label>
                        <input v-model="createForm.effective_from" type="date" class="w-full h-11 rounded-lg border-2 border-gray-300 px-3" :class="{ 'border-red-500': createForm.errors.effective_from }" />
                        <p v-if="createForm.errors.effective_from" class="mt-1 text-xs text-red-600">{{ createForm.errors.effective_from }}</p>
                    </div>
                </div>

                <!-- 作業方法ごとの単価 -->
                <div class="mt-5">
                    <h4 class="text-sm font-bold text-gray-700 mb-2">作業単価（円/kg・作業方法ごと）</h4>
                    <div v-for="(rate, i) in createForm.rates" :key="rate.work_method_id" class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-3">
                        <div class="font-semibold text-gray-800 mb-2">{{ rate.work_method_name }}</div>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">客先単価 *</label>
                                <input v-model.number="rate.rate_employee" type="number" step="0.01" min="0" class="w-full h-10 rounded-lg border-2 border-gray-300 px-2" :class="{ 'border-red-500': createForm.errors[`rates.${i}.rate_employee`] }" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">スタッフ単価 *</label>
                                <input v-model.number="rate.rate_contractor" type="number" step="0.01" min="0" class="w-full h-10 rounded-lg border-2 border-gray-300 px-2" :class="{ 'border-red-500': createForm.errors[`rates.${i}.rate_contractor`] }" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">スタッフ残業</label>
                                <input v-model.number="rate.rate_overtime" type="number" step="0.01" min="0" class="w-full h-10 rounded-lg border-2 border-gray-300 px-2" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="showCreateModal = false" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded-lg">
                        キャンセル
                    </button>
                    <button
                        type="button"
                        @click="submitCreate"
                        :disabled="createForm.processing"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-lg disabled:opacity-50"
                    >
                        {{ createForm.processing ? '登録中...' : '登録して紐づける' }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
