<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    staffInvoices: Array,
    filters: Object,
});

const form = useForm({
    staff_invoice_ids: [],
    period_from: '',
    period_to: '',
});

const periodFrom = ref(props.filters?.period_from || '');
const periodTo = ref(props.filters?.period_to || '');

const applyPeriodFilter = () => {
    router.get(route('client-invoices.create'), {
        period_from: periodFrom.value,
        period_to: periodTo.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearPeriodFilter = () => {
    periodFrom.value = '';
    periodTo.value = '';
    router.get(route('client-invoices.create'));
};

const toggleStaffInvoice = (id) => {
    const index = form.staff_invoice_ids.indexOf(id);
    if (index > -1) {
        form.staff_invoice_ids.splice(index, 1);
    } else {
        form.staff_invoice_ids.push(id);
    }
};

const selectedTotal = computed(() => {
    return props.staffInvoices
        .filter(invoice => form.staff_invoice_ids.includes(invoice.id))
        .reduce((sum, invoice) => sum + parseFloat(invoice.total), 0);
});

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('ja-JP');
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('ja-JP').format(num);
};

const submit = () => {
    form.post(route('client-invoices.store'));
};
</script>

<template>
    <Head title="客先請求書作成" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">客先請求書作成</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <form @submit.prevent="submit">
                        <div class="space-y-6">
                            <!-- 請求期間 -->
                            <div class="grid grid-cols-2 gap-4">
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

                            <!-- 期間フィルタ -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-sm font-medium text-gray-700 mb-2">スタッフ請求書の期間フィルタ</h3>
                                <div class="grid grid-cols-2 gap-4 mb-2">
                                    <div>
                                        <input
                                            v-model="periodFrom"
                                            type="date"
                                            class="block w-full rounded-md border-gray-300 shadow-sm"
                                        />
                                    </div>
                                    <div>
                                        <input
                                            v-model="periodTo"
                                            type="date"
                                            class="block w-full rounded-md border-gray-300 shadow-sm"
                                        />
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button
                                        type="button"
                                        @click="applyPeriodFilter"
                                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-1 px-3 rounded text-sm"
                                    >
                                        フィルタ適用
                                    </button>
                                    <button
                                        type="button"
                                        @click="clearPeriodFilter"
                                        class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-bold py-1 px-3 rounded text-sm"
                                    >
                                        クリア
                                    </button>
                                </div>
                            </div>

                            <!-- スタッフ請求書選択 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    スタッフ請求書選択 * ({{ form.staff_invoice_ids.length }}件選択中)
                                </label>
                                <div class="border border-gray-300 rounded-lg max-h-96 overflow-y-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50 sticky top-0">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                                    選択
                                                </th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                                    請求書番号
                                                </th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                                    スタッフ
                                                </th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                                    請求期間
                                                </th>
                                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">
                                                    金額
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr
                                                v-for="staffInvoice in staffInvoices"
                                                :key="staffInvoice.id"
                                                :class="form.staff_invoice_ids.includes(staffInvoice.id) ? 'bg-blue-50' : ''"
                                                class="hover:bg-gray-50 cursor-pointer"
                                                @click="toggleStaffInvoice(staffInvoice.id)"
                                            >
                                                <td class="px-4 py-2">
                                                    <input
                                                        type="checkbox"
                                                        :checked="form.staff_invoice_ids.includes(staffInvoice.id)"
                                                        @change="toggleStaffInvoice(staffInvoice.id)"
                                                        @click.stop
                                                        class="rounded border-gray-300 text-blue-600"
                                                    />
                                                </td>
                                                <td class="px-4 py-2 text-sm text-gray-900">
                                                    {{ staffInvoice.invoice_number }}
                                                </td>
                                                <td class="px-4 py-2 text-sm text-gray-900">
                                                    {{ staffInvoice.staff.name }}<br>
                                                    <span class="text-xs text-gray-500">{{ staffInvoice.staff.staff_type.name }}</span>
                                                </td>
                                                <td class="px-4 py-2 text-sm text-gray-900">
                                                    {{ formatDate(staffInvoice.period_from) }} ～ {{ formatDate(staffInvoice.period_to) }}
                                                </td>
                                                <td class="px-4 py-2 text-sm text-gray-900 text-right">
                                                    ¥{{ formatNumber(staffInvoice.total) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <p v-if="form.errors.staff_invoice_ids" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.staff_invoice_ids }}
                                </p>
                                <p v-if="staffInvoices.length === 0" class="mt-2 text-sm text-gray-500">
                                    選択可能なスタッフ請求書がありません。確定済みで未使用のスタッフ請求書が表示されます。
                                </p>
                            </div>

                            <!-- 選択合計 -->
                            <div v-if="form.staff_invoice_ids.length > 0" class="bg-blue-50 p-4 rounded-lg">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-700">選択したスタッフ請求書の合計:</span>
                                    <span class="text-lg font-bold text-blue-700">¥{{ formatNumber(selectedTotal) }}</span>
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
                                :href="route('client-invoices.index')"
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                            >
                                キャンセル
                            </a>
                            <button
                                type="submit"
                                :disabled="form.processing || form.staff_invoice_ids.length === 0"
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





