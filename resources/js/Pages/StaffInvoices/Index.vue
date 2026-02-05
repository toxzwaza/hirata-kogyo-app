<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    invoices: Object,
    staffList: Array,
    filters: Object,
});

const form = ref({
    staff_id: null,
    status: null,
    period_from: null,
    period_to: null,
});

const applyFilters = () => {
    router.get(route('staff-invoices.index'), form.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    form.value = {
        staff_id: null,
        status: null,
        period_from: null,
        period_to: null,
    };
    router.get(route('staff-invoices.index'));
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('ja-JP');
};

const formatNumber = (num) => {
    if (num === null || num === undefined) return '-';
    return new Intl.NumberFormat('ja-JP').format(num);
};

const getStatusLabel = (status) => {
    const labels = {
        draft: '下書き',
        fixed: '確定',
        paid: '支払済',
    };
    return labels[status] || status;
};

const getStatusClass = (status) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-800',
        fixed: 'bg-blue-100 text-blue-800',
        paid: 'bg-green-100 text-green-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="スタッフ請求書一覧" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">スタッフ請求書一覧</h2>
                <Link
                    :href="route('staff-invoices.create')"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                    新規作成
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- フィルター -->
                <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-medium mb-4">検索条件</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">スタッフ</label>
                            <select
                                v-model="form.staff_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >
                                <option :value="null">すべて</option>
                                <option
                                    v-for="staff in staffList"
                                    :key="staff.id"
                                    :value="staff.id"
                                >
                                    {{ staff.name }} ({{ staff.staff_type.name }})
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">ステータス</label>
                            <select
                                v-model="form.status"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >
                                <option :value="null">すべて</option>
                                <option value="draft">下書き</option>
                                <option value="fixed">確定</option>
                                <option value="paid">支払済</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">期間開始</label>
                            <input
                                v-model="form.period_from"
                                type="date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">期間終了</label>
                            <input
                                v-model="form.period_to"
                                type="date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            />
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <button
                            @click="applyFilters"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                        >
                            検索
                        </button>
                        <button
                            @click="clearFilters"
                            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                        >
                            クリア
                        </button>
                    </div>
                </div>

                <!-- 一覧テーブル -->
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    小計
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    消費税
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    合計
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ステータス
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    操作
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="invoice in invoices.data" :key="invoice.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ invoice.invoice_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ invoice.staff.name }}<br>
                                    <span class="text-xs text-gray-500">{{ invoice.staff.staff_type.name }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatDate(invoice.period_from) }} ～ {{ formatDate(invoice.period_to) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ¥{{ formatNumber(invoice.subtotal) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span v-if="invoice.tax !== null && invoice.tax !== undefined">
                                        ¥{{ formatNumber(invoice.tax) }}
                                    </span>
                                    <span v-else>-</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    ¥{{ formatNumber(invoice.total) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="getStatusClass(invoice.status)"
                                        class="px-2 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{ getStatusLabel(invoice.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <Link
                                        :href="route('staff-invoices.show', invoice.id)"
                                        class="text-blue-600 hover:text-blue-900"
                                    >
                                        確認
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- ページネーション -->
                    <div class="px-6 py-4 border-t border-gray-200" v-if="invoices.links">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                全 {{ invoices.total }} 件中 {{ invoices.from }} - {{ invoices.to }} 件を表示
                            </div>
                            <div class="flex gap-2">
                                <Link
                                    v-for="link in invoices.links"
                                    :key="link.label"
                                    :href="link.url || '#'"
                                    :class="[
                                        'px-3 py-2 rounded-md text-sm',
                                        link.active ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700',
                                        !link.url ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-300'
                                    ]"
                                    v-html="link.label"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>









