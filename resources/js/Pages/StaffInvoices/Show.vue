<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    invoice: Object,
});

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('ja-JP');
};

const formatDateTime = (dateTime) => {
    if (!dateTime) return '';
    return new Date(dateTime).toLocaleString('ja-JP');
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('ja-JP').format(num);
};

const fixInvoice = () => {
    if (confirm('この請求書を確定しますか？確定後は編集できません。')) {
        router.post(route('staff-invoices.fix', props.invoice.id));
    }
};

const downloadPdf = () => {
    window.location.href = route('staff-invoices.pdf', props.invoice.id);
};
</script>

<template>
    <Head :title="`請求書: ${invoice.invoice_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    スタッフ請求書: {{ invoice.invoice_number }}
                </h2>
                <div class="flex gap-2">
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
                                <p class="text-sm">{{ invoice.staff.name }}</p>
                                <p class="text-sm text-gray-600">{{ invoice.staff.staff_type.name }}</p>
                                <p v-if="invoice.staff.address" class="text-sm text-gray-600 mt-2">
                                    {{ invoice.staff.address }}
                                </p>
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
                                            'text-green-600': invoice.status === 'paid',
                                        }"
                                        class="font-bold"
                                    >
                                        {{ invoice.status === 'draft' ? '下書き' : invoice.status === 'fixed' ? '確定' : '支払済' }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 明細テーブル -->
                    <div class="mb-8">
                        <h3 class="text-lg font-bold mb-4">明細</h3>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        説明
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        数量（kg）
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        単価（円/kg）
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        金額
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="detail in invoice.details" :key="detail.id">
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ detail.description }}
                                        <br>
                                        <span class="text-xs text-gray-500">
                                            図番: {{ detail.work_record.drawing.drawing_number }} |
                                            客先: {{ detail.work_record.drawing.client.name }} |
                                            作業日時: {{ formatDateTime(detail.work_record.start_time) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-right">
                                        {{ formatNumber(detail.quantity) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-right">
                                        ¥{{ formatNumber(detail.unit_price) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-right">
                                        ¥{{ formatNumber(detail.amount) }}
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

