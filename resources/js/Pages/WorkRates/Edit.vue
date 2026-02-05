<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    workRate: Object,
    drawings: Array,
    workMethods: Array,
});

// type="date" 用に YYYY-MM-DD 形式に正規化（登録済みの日付をデフォルト表示するため）
const toDateInputValue = (val) => {
    if (val == null) return '';
    if (typeof val === 'string') return val.substring(0, 10);
    if (typeof val === 'object' && val instanceof Date) return val.toISOString().slice(0, 10);
    return '';
};

const form = useForm({
    drawing_id: props.workRate.drawing_id,
    work_method_id: props.workRate.work_method_id,
    rate_employee: props.workRate.rate_employee,
    rate_contractor: props.workRate.rate_contractor,
    rate_overtime: props.workRate.rate_overtime,
    note: props.workRate.note || '',
    effective_from: toDateInputValue(props.workRate.effective_from),
    effective_to: toDateInputValue(props.workRate.effective_to) || null,
    active_flg: props.workRate.active_flg !== false,
});

const submit = () => {
    form.put(route('work-rates.update', props.workRate.id));
};

const deleteRate = () => {
    if (confirm('この作業単価を削除しますか？')) {
        form.delete(route('work-rates.destroy', props.workRate.id));
    }
};
</script>

<template>
    <Head title="作業単価編集" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">作業単価編集</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <form @submit.prevent="submit">
                        <div class="space-y-6">
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

                            <!-- 正社員用単価 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">正社員用単価（円/kg） *</label>
                                <input
                                    v-model.number="form.rate_employee"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :class="{ 'border-red-500': form.errors.rate_employee }"
                                />
                                <p v-if="form.errors.rate_employee" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.rate_employee }}
                                </p>
                            </div>

                            <!-- 個人事業主用通常単価 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">個人事業主用通常単価（円/kg） *</label>
                                <input
                                    v-model.number="form.rate_contractor"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :class="{ 'border-red-500': form.errors.rate_contractor }"
                                />
                                <p v-if="form.errors.rate_contractor" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.rate_contractor }}
                                </p>
                            </div>

                            <!-- 個人事業主用残業単価 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">個人事業主用残業単価（円/kg） *</label>
                                <input
                                    v-model.number="form.rate_overtime"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :class="{ 'border-red-500': form.errors.rate_overtime }"
                                />
                                <p v-if="form.errors.rate_overtime" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.rate_overtime }}
                                </p>
                            </div>

                            <!-- 適用フラグ -->
                            <div>
                                <label class="flex items-center gap-2">
                                    <input
                                        v-model="form.active_flg"
                                        type="checkbox"
                                        class="rounded border-gray-300"
                                    />
                                    <span class="text-sm font-medium text-gray-700">適用する（作業実績で使用する単価として有効）</span>
                                </label>
                                <p v-if="form.errors.active_flg" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.active_flg }}
                                </p>
                            </div>

                            <!-- 適用開始日 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">適用開始日 *</label>
                                <input
                                    v-model="form.effective_from"
                                    type="date"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :class="{ 'border-red-500': form.errors.effective_from }"
                                />
                                <p v-if="form.errors.effective_from" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.effective_from }}
                                </p>
                            </div>

                            <!-- 適用終了日 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">適用終了日（空欄の場合は無期限）</label>
                                <input
                                    v-model="form.effective_to"
                                    type="date"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :class="{ 'border-red-500': form.errors.effective_to }"
                                />
                                <p v-if="form.errors.effective_to" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.effective_to }}
                                </p>
                            </div>

                            <!-- 備考 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">備考</label>
                                <textarea
                                    v-model="form.note"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                />
                            </div>
                        </div>

                        <div v-if="form.errors.error" class="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            {{ form.errors.error }}
                        </div>

                        <div class="mt-6 flex justify-between">
                            <button
                                type="button"
                                @click="deleteRate"
                                class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                            >
                                削除
                            </button>
                            <div class="flex gap-4">
                                <a
                                    :href="route('work-rates.index')"
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
















