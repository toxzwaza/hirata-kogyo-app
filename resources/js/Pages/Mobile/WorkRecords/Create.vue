<script setup>
import MobileLayout from '@/Layouts/MobileLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    currentStaff: Object,
    drawings: Array,
    workMethods: Array,
    defectTypes: Array,
});

// 得意先リスト（重複なし）
const clients = computed(() => {
    const clientMap = new Map();
    props.drawings.forEach(drawing => {
        if (drawing.client && !clientMap.has(drawing.client.id)) {
            clientMap.set(drawing.client.id, drawing.client);
        }
    });
    return Array.from(clientMap.values()).sort((a, b) => a.name.localeCompare(b.name));
});

// フィルター用のリアクティブ変数
const clientFilter = ref('');
const drawingNumberFilter = ref('');

// フィルターされた図番リスト
const filteredDrawings = computed(() => {
    let filtered = props.drawings;
    
    // 得意先でフィルター
    if (clientFilter.value) {
        filtered = filtered.filter(d => d.client && d.client.id === parseInt(clientFilter.value));
    }
    
    // 図番でフィルター（部分一致）
    if (drawingNumberFilter.value) {
        const searchText = drawingNumberFilter.value.toLowerCase();
        filtered = filtered.filter(d => 
            d.drawing_number.toLowerCase().includes(searchText)
        );
    }
    
    return filtered;
});

// 図番入力で選択された場合の処理
const onDrawingNumberInput = (event) => {
    const inputValue = event.target.value.trim();
    if (!inputValue) {
        form.drawing_id = '';
        form.work_method_id = '';
        return;
    }
    
    // 完全一致する図番を探す
    const matchedDrawing = filteredDrawings.value.find(d => d.drawing_number === inputValue);
    if (matchedDrawing) {
        form.drawing_id = matchedDrawing.id;
        form.work_method_id = ''; // 図番が変わったら作業方法をリセット
        console.log('Drawing selected:', matchedDrawing.id, matchedDrawing.drawing_number, 'work_rates:', matchedDrawing.work_rates);
    } else {
        // 完全一致しない場合は、部分一致で最初に見つかったものを設定
        const partialMatch = filteredDrawings.value.find(d => 
            d.drawing_number.toLowerCase().startsWith(inputValue.toLowerCase())
        );
        if (partialMatch && filteredDrawings.value.length === 1) {
            form.drawing_id = partialMatch.id;
            form.work_method_id = ''; // 図番が変わったら作業方法をリセット
            console.log('Drawing selected (partial match):', partialMatch.id, partialMatch.drawing_number, 'work_rates:', partialMatch.work_rates);
        } else {
            form.drawing_id = '';
            form.work_method_id = '';
        }
    }
};

// 現在時刻をデフォルト値として設定
const now = new Date();
const formatDateTimeLocal = (date) => {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
};

const defaultStartTime = formatDateTimeLocal(now);
const defaultEndTime = formatDateTimeLocal(new Date(now.getTime() + 60 * 60 * 1000)); // 1時間後

const form = useForm({
    drawing_id: '',
    work_method_id: '',
    staff_id: props.currentStaff.id,
    start_time: defaultStartTime,
    end_time: defaultEndTime,
    quantity_good: 0,
    quantity_ng: 0,
    memo: '',
    defects: [],
});

const addDefect = () => {
    form.defects.push({
        defect_type_id: '',
        defect_quantity: 1,
    });
};

const removeDefect = (index) => {
    form.defects.splice(index, 1);
};

const totalDefectQuantity = computed(() => {
    return form.defects.reduce((sum, defect) => sum + (parseInt(defect.defect_quantity) || 0), 0);
});

const isDefectQuantityValid = computed(() => {
    return totalDefectQuantity.value === form.quantity_ng;
});

// 選択された図番に関連する作業方法のみをフィルター
const availableWorkMethods = computed(() => {
    if (!form.drawing_id) {
        return [];
    }
    
    // drawing_idを数値に変換して比較
    const drawingId = typeof form.drawing_id === 'string' ? parseInt(form.drawing_id) : form.drawing_id;
    
    const selectedDrawing = props.drawings.find(d => {
        const dId = typeof d.id === 'string' ? parseInt(d.id) : d.id;
        return dId === drawingId;
    });
    
    if (!selectedDrawing) {
        console.log('Drawing not found:', drawingId, 'Available drawings:', props.drawings.map(d => ({ id: d.id, drawing_number: d.drawing_number })));
        return [];
    }
    
    console.log('Selected drawing:', selectedDrawing);
    console.log('Drawing keys:', Object.keys(selectedDrawing));
    console.log('Drawing has workRates:', 'workRates' in selectedDrawing);
    console.log('Drawing has work_rates:', 'work_rates' in selectedDrawing);
    
    // work_ratesまたはworkRatesのどちらかを使用（Laravelのリレーション名の変換に対応）
    // Inertiaでは通常キャメルケースのまま送信される
    const workRates = selectedDrawing.workRates || selectedDrawing.work_rates || [];
    
    console.log('workRates:', workRates);
    console.log('workRates type:', typeof workRates);
    console.log('workRates is array:', Array.isArray(workRates));
    console.log('workRates length:', workRates?.length);
    
    // もしworkRatesが読み込まれていない場合、全drawingsを確認
    if (!workRates || workRates.length === 0) {
        console.log('Checking all drawings for workRates...');
        props.drawings.forEach((d, index) => {
            if (index < 3) { // 最初の3つだけ確認
                console.log(`Drawing ${index}:`, {
                    id: d.id,
                    drawing_number: d.drawing_number,
                    hasWorkRates: 'workRates' in d,
                    hasWork_rates: 'work_rates' in d,
                    workRates: d.workRates || d.work_rates || 'not found'
                });
            }
        });
    }
    
    if (!workRates || workRates.length === 0) {
        console.log('No work_rates found for drawing:', selectedDrawing.id);
        console.log('Full drawing object:', JSON.stringify(selectedDrawing, null, 2));
        return [];
    }
    
    // work_ratesからwork_method_idを取得（重複を除去）
    const workMethodIds = new Set();
    workRates.forEach(rate => {
        if (rate.work_method_id) {
            const methodId = typeof rate.work_method_id === 'string' ? parseInt(rate.work_method_id) : rate.work_method_id;
            workMethodIds.add(methodId);
        }
    });
    
    console.log('Work method IDs from work_rates:', Array.from(workMethodIds));
    console.log('Available work methods:', props.workMethods.map(m => ({ id: m.id, name: m.name })));
    
    // 対応する作業方法を返す
    const filtered = props.workMethods.filter(method => {
        const methodId = typeof method.id === 'string' ? parseInt(method.id) : method.id;
        return workMethodIds.has(methodId);
    });
    
    console.log('Filtered work methods:', filtered);
    return filtered;
});

const submit = () => {
    form.post(route('staff.work-records.store'));
};
</script>

<template>
    <Head title="作業実績登録" />

    <MobileLayout title="作業実績登録">
        <!-- 成功メッセージ -->
        <div v-if="$page.props.flash?.success" class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            {{ $page.props.flash.success }}
        </div>

        <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
            <div class="text-sm text-gray-600 mb-2">ログイン中: {{ currentStaff.name }}</div>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- 図番選択（2カラム：得意先と図番入力） -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">図番 *</label>
                <div class="grid grid-cols-2 gap-2">
                    <!-- 得意先フィルター（左） -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">得意先</label>
                                    <select
                                        v-model="clientFilter"
                                        class="w-full h-12 text-base rounded-lg border-gray-300 shadow-sm"
                                        @change="drawingNumberFilter = ''; form.drawing_id = ''; form.work_method_id = ''"
                                    >
                            <option value="">すべて</option>
                            <option
                                v-for="client in clients"
                                :key="client.id"
                                :value="client.id"
                            >
                                {{ client.name }}
                            </option>
                        </select>
                    </div>
                    
                    <!-- 図番入力（右、datalist使用） -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">図番</label>
                        <input
                            v-model="drawingNumberFilter"
                            type="text"
                            list="drawing-number-list"
                            placeholder="図番を入力"
                            class="w-full h-12 text-base rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200"
                            :class="{ 'border-red-500': form.errors.drawing_id }"
                            @input="onDrawingNumberInput"
                        />
                        <datalist id="drawing-number-list">
                            <option
                                v-for="drawing in filteredDrawings"
                                :key="drawing.id"
                                :value="drawing.drawing_number"
                            >
                                {{ drawing.drawing_number }} - {{ drawing.product_name }} ({{ drawing.client.name }})
                            </option>
                        </datalist>
                    </div>
                </div>
                <p v-if="form.errors.drawing_id" class="mt-1 text-sm text-red-600">
                    {{ form.errors.drawing_id }}
                </p>
            </div>

            <!-- スタッフと作業方法（2カラム） -->
            <div class="grid grid-cols-2 gap-4">
                <!-- 作業方法 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">作業方法 *</label>
                    <select
                        v-model="form.work_method_id"
                        class="w-full h-12 text-base rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200"
                        :class="{ 'border-red-500': form.errors.work_method_id }"
                        :disabled="!form.drawing_id"
                    >
                        <option value="">{{ form.drawing_id ? '選択してください' : '図番を選択してください' }}</option>
                        <option
                            v-for="method in availableWorkMethods"
                            :key="method.id"
                            :value="method.id"
                        >
                            {{ method.name }}
                        </option>
                    </select>
                    <p v-if="form.errors.work_method_id" class="mt-1 text-sm text-red-600">
                        {{ form.errors.work_method_id }}
                    </p>
                    <p v-if="!form.drawing_id" class="mt-1 text-xs text-gray-500">
                        図番を選択すると作業方法が表示されます
                    </p>
                </div>
            </div>

            <!-- 作業開始時刻 -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">作業開始時刻 *</label>
                <input
                    v-model="form.start_time"
                    type="datetime-local"
                    class="w-full h-12 text-base rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200"
                    :class="{ 'border-red-500': form.errors.start_time }"
                />
                <p v-if="form.errors.start_time" class="mt-1 text-sm text-red-600">
                    {{ form.errors.start_time }}
                </p>
            </div>

            <!-- 作業終了時刻 -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">作業終了時刻 *</label>
                <input
                    v-model="form.end_time"
                    type="datetime-local"
                    class="w-full h-12 text-base rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200"
                    :class="{ 'border-red-500': form.errors.end_time }"
                />
                <p v-if="form.errors.end_time" class="mt-1 text-sm text-red-600">
                    {{ form.errors.end_time }}
                </p>
            </div>

            <!-- 良品数 -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">良品数 *</label>
                <input
                    v-model.number="form.quantity_good"
                    type="number"
                    min="0"
                    class="w-full h-12 text-base rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200"
                    :class="{ 'border-red-500': form.errors.quantity_good }"
                />
                <p v-if="form.errors.quantity_good" class="mt-1 text-sm text-red-600">
                    {{ form.errors.quantity_good }}
                </p>
            </div>

            <!-- 不良数 -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">不良数 *</label>
                <input
                    v-model.number="form.quantity_ng"
                    type="number"
                    min="0"
                    class="w-full h-12 text-base rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200"
                    :class="{ 'border-red-500': form.errors.quantity_ng }"
                />
                <p v-if="form.errors.quantity_ng" class="mt-1 text-sm text-red-600">
                    {{ form.errors.quantity_ng }}
                </p>
            </div>

            <!-- 備考 -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">備考</label>
                <textarea
                    v-model="form.memo"
                    rows="3"
                    class="w-full text-base rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200"
                    :class="{ 'border-red-500': form.errors.memo }"
                />
                <p v-if="form.errors.memo" class="mt-1 text-sm text-red-600">
                    {{ form.errors.memo }}
                </p>
            </div>

            <!-- 不良内訳 -->
            <div>
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-sm font-medium text-gray-700">不良内訳</label>
                    <button
                        type="button"
                        @click="addDefect"
                        class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg text-sm"
                    >
                        追加
                    </button>
                </div>
                <div v-if="form.defects.length > 0" class="space-y-3">
                    <div
                        v-for="(defect, index) in form.defects"
                        :key="index"
                        class="flex gap-2 items-end"
                    >
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-2">不良種類</label>
                            <select
                                v-model="defect.defect_type_id"
                                class="w-full h-12 text-base rounded-lg border-gray-300 shadow-sm"
                            >
                                <option value="">選択してください</option>
                                <option
                                    v-for="type in defectTypes"
                                    :key="type.id"
                                    :value="type.id"
                                >
                                    {{ type.name }}
                                </option>
                            </select>
                        </div>
                        <div class="w-24">
                            <label class="block text-sm font-medium text-gray-700 mb-2">数量</label>
                            <input
                                v-model.number="defect.defect_quantity"
                                type="number"
                                min="1"
                                class="w-full h-12 text-base rounded-lg border-gray-300 shadow-sm"
                            />
                        </div>
                        <button
                            type="button"
                            @click="removeDefect(index)"
                            class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-3 rounded-lg h-12"
                        >
                            削除
                        </button>
                    </div>
                    <div class="text-sm" :class="isDefectQuantityValid ? 'text-gray-600' : 'text-red-600'">
                        合計: {{ totalDefectQuantity }} / 不良数: {{ form.quantity_ng }}
                        <span v-if="!isDefectQuantityValid">
                            （不一致）
                        </span>
                    </div>
                </div>
            </div>

            <!-- エラーメッセージ -->
            <div v-if="form.errors.error" class="p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                {{ form.errors.error }}
            </div>

            <!-- 送信ボタン -->
            <div class="pt-4 pb-8">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full h-14 bg-blue-500 hover:bg-blue-600 text-white font-bold text-lg rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{ form.processing ? '登録中...' : '登録' }}
                </button>
            </div>
        </form>
    </MobileLayout>
</template>

