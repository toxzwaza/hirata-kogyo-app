<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    staffList: Array,
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
    
    // work_ratesまたはworkRatesのどちらかを使用（Laravelのリレーション名の変換に対応）
    const workRates = selectedDrawing.work_rates || selectedDrawing.workRates || [];
    
    if (!workRates || workRates.length === 0) {
        console.log('No work_rates found for drawing:', selectedDrawing.id, selectedDrawing);
        console.log('Available keys:', Object.keys(selectedDrawing));
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

const form = useForm({
    drawing_id: '',
    work_method_id: '',
    staff_id: '',
    start_time: '',
    end_time: '',
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

const totalDefectQuantity = () => {
    return form.defects.reduce((sum, defect) => sum + (parseInt(defect.defect_quantity) || 0), 0);
};

const submit = () => {
    form.post(route('work-records.store'));
};
</script>

<template>
    <Head title="作業実績登録" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">作業実績登録</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <form @submit.prevent="submit">
                        <!-- 図番選択（2カラム：得意先と図番入力） -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">図番 *</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- 得意先フィルター（左） -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">得意先</label>
                                    <select
                                        v-model="clientFilter"
                                        class="block w-full rounded-md border-gray-300 shadow-sm"
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
                                        class="block w-full rounded-md border-gray-300 shadow-sm"
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
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- スタッフ -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">スタッフ *</label>
                                <select
                                    v-model="form.staff_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :class="{ 'border-red-500': form.errors.staff_id }"
                                >
                                    <option value="">選択してください</option>
                                    <option
                                        v-for="staff in staffList"
                                        :key="staff.id"
                                        :value="staff.id"
                                    >
                                        {{ staff.name }} ({{ staff.staff_type.name }})
                                    </option>
                                </select>
                                <p v-if="form.errors.staff_id" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.staff_id }}
                                </p>
                            </div>

                            <!-- 作業方法 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">作業方法 *</label>
                                <select
                                    v-model="form.work_method_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
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

                        <!-- 作業開始時刻、終了時刻、良品数、不良数（縦に並べる） -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- 作業開始時刻 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">作業開始時刻 *</label>
                                <input
                                    v-model="form.start_time"
                                    type="datetime-local"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :class="{ 'border-red-500': form.errors.start_time }"
                                />
                                <p v-if="form.errors.start_time" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.start_time }}
                                </p>
                            </div>

                            <!-- 作業終了時刻 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">作業終了時刻 *</label>
                                <input
                                    v-model="form.end_time"
                                    type="datetime-local"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :class="{ 'border-red-500': form.errors.end_time }"
                                />
                                <p v-if="form.errors.end_time" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.end_time }}
                                </p>
                            </div>

                            <!-- 良品数 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">良品数 *</label>
                                <input
                                    v-model.number="form.quantity_good"
                                    type="number"
                                    min="0"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :class="{ 'border-red-500': form.errors.quantity_good }"
                                />
                                <p v-if="form.errors.quantity_good" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.quantity_good }}
                                </p>
                            </div>

                            <!-- 不良数 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">不良数 *</label>
                                <input
                                    v-model.number="form.quantity_ng"
                                    type="number"
                                    min="0"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :class="{ 'border-red-500': form.errors.quantity_ng }"
                                />
                                <p v-if="form.errors.quantity_ng" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.quantity_ng }}
                                </p>
                            </div>
                        </div>

                        <!-- 備考 -->
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700">備考</label>
                            <textarea
                                v-model="form.memo"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                :class="{ 'border-red-500': form.errors.memo }"
                            />
                            <p v-if="form.errors.memo" class="mt-1 text-sm text-red-600">
                                {{ form.errors.memo }}
                            </p>
                        </div>

                        <!-- 不良内訳 -->
                        <div class="mt-6">
                            <div class="flex justify-between items-center mb-4">
                                <label class="block text-sm font-medium text-gray-700">不良内訳</label>
                                <button
                                    type="button"
                                    @click="addDefect"
                                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-1 px-3 rounded text-sm"
                                >
                                    追加
                                </button>
                            </div>
                            <div v-if="form.defects.length > 0" class="space-y-2">
                                <div
                                    v-for="(defect, index) in form.defects"
                                    :key="index"
                                    class="flex gap-2 items-end"
                                >
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium text-gray-700">不良種類</label>
                                        <select
                                            v-model="defect.defect_type_id"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
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
                                    <div class="w-32">
                                        <label class="block text-sm font-medium text-gray-700">数量</label>
                                        <input
                                            v-model.number="defect.defect_quantity"
                                            type="number"
                                            min="1"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeDefect(index)"
                                        class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-3 rounded"
                                    >
                                        削除
                                    </button>
                                </div>
                                <div class="text-sm text-gray-600">
                                    合計: {{ totalDefectQuantity() }} / 不良数: {{ form.quantity_ng }}
                                    <span
                                        v-if="totalDefectQuantity() !== form.quantity_ng"
                                        class="text-red-600"
                                    >
                                        （不一致）
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- エラーメッセージ -->
                        <div v-if="form.errors.error" class="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            {{ form.errors.error }}
                        </div>

                        <!-- ボタン -->
                        <div class="mt-6 flex justify-end gap-4">
                            <a
                                :href="route('work-records.index')"
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                            >
                                キャンセル
                            </a>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                            >
                                {{ form.processing ? '登録中...' : '登録' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>


