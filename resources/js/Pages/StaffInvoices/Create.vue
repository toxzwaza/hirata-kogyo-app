<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    staffList: Array,
});

const form = useForm({
    staff_id: '',
    period_from: '',
    period_to: '',
});

const submit = () => {
    form.post(route('staff-invoices.store'));
};
</script>

<template>
    <Head title="スタッフ請求書作成" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">スタッフ請求書作成</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <form @submit.prevent="submit">
                        <div class="space-y-6">
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

                            <!-- 請求期間開始 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">請求期間開始日 *</label>
                                <input
                                    v-model="form.period_from"
                                    type="date"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :class="{ 'border-red-500': form.errors.period_from }"
                                />
                                <p v-if="form.errors.period_from" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.period_from }}
                                </p>
                            </div>

                            <!-- 請求期間終了 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">請求期間終了日 *</label>
                                <input
                                    v-model="form.period_to"
                                    type="date"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    :class="{ 'border-red-500': form.errors.period_to }"
                                />
                                <p v-if="form.errors.period_to" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.period_to }}
                                </p>
                            </div>
                        </div>

                        <!-- エラーメッセージ -->
                        <div v-if="form.errors.error" class="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            {{ form.errors.error }}
                        </div>

                        <!-- ボタン -->
                        <div class="mt-6 flex justify-end gap-4">
                            <a
                                :href="route('staff-invoices.index')"
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                            >
                                キャンセル
                            </a>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                            >
                                {{ form.processing ? '作成中...' : '作成' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>








