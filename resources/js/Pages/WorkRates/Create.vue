<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    drawings: Array,
    workMethods: Array,
});

const form = useForm({
    drawing_id: '',
    work_method_id: '',
    rate_employee: 0,
    rate_contractor: 0,
    rate_overtime: 0,
    note: '',
    effective_from: '',
    effective_to: null,
});

const submit = () => {
    form.post(route('work-rates.store'));
};
</script>

<template>
    <Head title="作業単価登録" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">作業単価登録</h2>
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

                        <div class="mt-6 flex justify-end gap-4">
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
                                {{ form.processing ? '登録中...' : '登録' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>










