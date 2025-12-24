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
            <!-- 図番 -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">図番 *</label>
                <select
                    v-model="form.drawing_id"
                    class="w-full h-12 text-base rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200"
                    :class="{ 'border-red-500': form.errors.drawing_id }"
                >
                    <option value="">選択してください</option>
                    <option
                        v-for="drawing in drawings"
                        :key="drawing.id"
                        :value="drawing.id"
                    >
                        {{ drawing.drawing_number }} - {{ drawing.product_name }} ({{ drawing.client.name }})
                    </option>
                </select>
                <p v-if="form.errors.drawing_id" class="mt-1 text-sm text-red-600">
                    {{ form.errors.drawing_id }}
                </p>
            </div>

            <!-- 作業方法 -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">作業方法 *</label>
                <select
                    v-model="form.work_method_id"
                    class="w-full h-12 text-base rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200"
                    :class="{ 'border-red-500': form.errors.work_method_id }"
                >
                    <option value="">選択してください</option>
                    <option
                        v-for="method in workMethods"
                        :key="method.id"
                        :value="method.id"
                    >
                        {{ method.name }}
                    </option>
                </select>
                <p v-if="form.errors.work_method_id" class="mt-1 text-sm text-red-600">
                    {{ form.errors.work_method_id }}
                </p>
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

