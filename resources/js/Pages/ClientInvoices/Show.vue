<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    invoice: Object,
});

const showAdjustmentForm = ref(false);

const adjustmentForm = useForm({
    adjustment_amount: props.invoice.adjustment_amount || 0,
    adjustment_reason: props.invoice.adjustment_reason || '',
});

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('ja-JP');
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('ja-JP').format(num);
};

const fixInvoice = () => {
    if (confirm('この請求書を確定しますか？確定後は編集できません。')) {
        router.post(route('client-invoices.fix', props.invoice.id));
    }
};

const submitAdjustment = () => {
    adjustmentForm.post(route('client-invoices.update-adjustment', props.invoice.id), {
        onSuccess: () => {
            showAdjustmentForm = false;
        },
    });
};

const downloadPdf = () => {
    window.location.href = route('client-invoices.pdf', props.invoice.id);
};
</script>

<template>
    <Head :title="`請求書: ${invoice.invoice_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    客先請求書: {{ invoice.invoice_number }}
                </h2>
                <div class="flex gap-2">
                    <button
                        v-if="invoice.status === 'draft' && !showAdjustmentForm"
                        @click="showAdjustmentForm = true"
                        class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded"
                    >
                        差額調整
                    </button>
                    <button
                        v-if="invoice.status === 'draft'"
                        @click="fixInvoice"
                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                    >
                        確定
                    </button>
                    <button
                        @click="downloadPdf"
                        class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                    >
                        PDF出力
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <!-- 請求書ヘッダー -->
                    <div class="mb-8">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h3 class="text-lg font-bold mb-2">請求先</h3>
                                <p class="text-sm">{{ invoice.client.name }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-600">請求書番号: {{ invoice.invoice_number }}</p>
                                <p class="text-sm text-gray-600">発行日: {{ formatDate(invoice.issue_date) }}</p>
                                <p class="text-sm text-gray-600">
                                    請求期間: {{ formatDate(invoice.period_from) }} ～ {{ formatDate(invoice.period_to) }}
                                </p>
                                <p class="text-sm text-gray-600 mt-2">
                                    ステータス:
                                    <span
                                        :class="{
                                            'text-gray-600': invoice.status === 'draft',
                                            'text-blue-600': invoice.status === 'fixed',
                                            'text-green-600': invoice.status === 'issued',
                                        }"
                                        class="font-bold"
                                    >
                                        {{ invoice.status === 'draft' ? '下書き' : invoice.status === 'fixed' ? '確定' : '発行済' }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 差額調整フォーム -->
                    <div v-if="showAdjustmentForm && invoice.status === 'draft'" class="mb-8 bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                        <h3 class="text-lg font-bold mb-4">差額調整</h3>
                        <form @submit.prevent="submitAdjustment">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">差額調整金額 *</label>
                                    <input
                                        v-model.number="adjustmentForm.adjustment_amount"
                                        type="number"
                                        step="0.01"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                        :class="{ 'border-red-500': adjustmentForm.errors.adjustment_amount }"
                                    />
                                    <p v-if="adjustmentForm.errors.adjustment_amount" class="mt-1 text-sm text-red-600">
                                        {{ adjustmentForm.errors.adjustment_amount }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">差額調整理由</label>
                                    <input
                                        v-model="adjustmentForm.adjustment_reason"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                        :class="{ 'border-red-500': adjustmentForm.errors.adjustment_reason }"
                                    />
                                    <p v-if="adjustmentForm.errors.adjustment_reason" class="mt-1 text-sm text-red-600">
                                        {{ adjustmentForm.errors.adjustment_reason }}
                                    </p>
                                </div>
                                <div class="flex gap-2">
                                    <button
                                        type="submit"
                                        :disabled="adjustmentForm.processing"
                                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                                    >
                                        {{ adjustmentForm.processing ? '更新中...' : '更新' }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="showAdjustmentForm = false"
                                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                                    >
                                        キャンセル
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- 含まれるスタッフ請求書 -->
                    <div class="mb-8">
                        <h3 class="text-lg font-bold mb-4">含まれるスタッフ請求書</h3>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        請求書番号
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        スタッフ
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        請求期間
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        金額
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="item in invoice.staff_invoice_items" :key="item.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ item.staff_invoice.invoice_number }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ item.staff_invoice.staff.name }}<br>
                                        <span class="text-xs text-gray-500">{{ item.staff_invoice.staff.staff_type.name }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatDate(item.staff_invoice.period_from) }} ～ {{ formatDate(item.staff_invoice.period_to) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        ¥{{ formatNumber(item.staff_invoice.total) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- 合計 -->
                    <div class="flex justify-end">
                        <div class="w-64">
                            <div class="flex justify-between py-2 border-b">
                                <span class="text-sm font-medium">小計</span>
                                <span class="text-sm">¥{{ formatNumber(invoice.subtotal) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b">
                                <span class="text-sm font-medium">差額調整</span>
                                <span class="text-sm">¥{{ formatNumber(invoice.adjustment_amount) }}</span>
                            </div>
                            <div v-if="invoice.adjustment_reason" class="text-xs text-gray-500 mb-2">
                                理由: {{ invoice.adjustment_reason }}
                            </div>
                            <div class="flex justify-between py-2 border-b">
                                <span class="text-sm font-medium">消費税</span>
                                <span class="text-sm">¥{{ formatNumber(invoice.tax) }}</span>
                            </div>
                            <div class="flex justify-between py-2">
                                <span class="text-lg font-bold">合計</span>
                                <span class="text-lg font-bold">¥{{ formatNumber(invoice.total) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

