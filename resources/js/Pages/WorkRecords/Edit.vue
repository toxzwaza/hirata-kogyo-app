<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const props = defineProps({
    workRecord: Object,
    staffList: Array,
    drawings: Array,
    workMethods: Array,
    defectTypes: Array,
});

const form = useForm({
    drawing_id: props.workRecord.drawing_id,
    work_method_id: props.workRecord.work_method_id,
    staff_id: props.workRecord.staff_id,
    start_time: props.workRecord.start_time ? new Date(props.workRecord.start_time).toISOString().slice(0, 16) : '',
    end_time: props.workRecord.end_time ? new Date(props.workRecord.end_time).toISOString().slice(0, 16) : '',
    quantity_good: props.workRecord.quantity_good,
    quantity_ng: props.workRecord.quantity_ng,
    memo: props.workRecord.memo || '',
    defects: props.workRecord.defects ? props.workRecord.defects.map(d => ({
        defect_type_id: d.defect_type_id,
        defect_quantity: d.defect_quantity,
    })) : [],
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
    form.put(route('work-records.update', props.workRecord.id));
};

const deleteRecord = () => {
    if (confirm('この作業実績を削除しますか？')) {
        form.delete(route('work-records.destroy', props.workRecord.id));
    }
};
</script>

<template>
    <Head title="作業実績編集" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">作業実績編集</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- 図番 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">図番 *</label>
                                <select
                                    v-model="form.drawing_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
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
                                <label class="block text-sm font-medium text-gray-700">作業方法 *</label>
                                <select
                                    v-model="form.work_method_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
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
                        <div class="mt-6 flex justify-between">
                            <button
                                type="button"
                                @click="deleteRecord"
                                class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                            >
                                削除
                            </button>
                            <div class="flex gap-4">
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
                                    {{ form.processing ? '更新中...' : '更新' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>



