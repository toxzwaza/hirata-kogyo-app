<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Invoice from '@/Components/Invoice.vue';

const props = defineProps({
    invoice: Object,
});

// 日付のみ文字列をローカル日付としてパース（タイムゾーンずれ防止）
const parseLocalDate = (dateStr) => {
    if (!dateStr) return null;
    const s = typeof dateStr === 'string' ? dateStr.slice(0, 10) : dateStr;
    if (typeof s !== 'string' || s.length < 10) return null;
    const [y, m, d] = s.split('-').map(Number);
    if (isNaN(y) || isNaN(m) || isNaN(d)) return null;
    return new Date(y, m - 1, d);
};
// ローカル日付を Y-m-d に
const toYmd = (date) => {
    if (!date) return '';
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
};
// 支払い期限の実効値（Y-m-d）。保存済みがあればそれ、なければ発行日+30日
const getEffectiveDueDateYmd = (inv) => {
    if (inv?.payment_due_date) {
        const s = typeof inv.payment_due_date === 'string' ? inv.payment_due_date : inv.payment_due_date;
        return s.slice(0, 10);
    }
    const issue = parseLocalDate(inv?.issue_date);
    if (issue) {
        issue.setDate(issue.getDate() + 30);
        return toYmd(issue);
    }
    return '';
};

const paymentDueDateForm = useForm({
    payment_due_date: getEffectiveDueDateYmd(props.invoice),
});

const formatDate = (date) => {
    if (!date) return '';
    const d = typeof date === 'string' && /^\d{4}-\d{2}-\d{2}/.test(date.slice(0, 10))
        ? parseLocalDate(date)
        : new Date(date);
    if (!d || isNaN(d.getTime())) return '';
    const year = d.getFullYear();
    const month = d.getMonth() + 1;
    const day = d.getDate();
    return `${year}年${month}月${day}日`;
};

const formatDateTime = (dateTime) => {
    if (!dateTime) return '';
    return new Date(dateTime).toLocaleString('ja-JP');
};

const formatNumber = (num) => {
    if (num === null || num === undefined) return '-';
    return new Intl.NumberFormat('ja-JP').format(num);
};

const fixInvoice = () => {
    if (confirm('この請求書を確定しますか？確定後は編集できません。')) {
        router.post(route('staff-invoices.fix', props.invoice.id));
    }
};

const revertToDraft = () => {
    if (confirm('この請求書を下書きに戻しますか？')) {
        router.post(route('staff-invoices.unfix', props.invoice.id), {}, {
            onError: (errors) => {
                if (errors.error) alert(errors.error);
            },
        });
    }
};

const deleteInvoice = () => {
    if (confirm('この請求書を削除しますか？この操作は取り消せません。')) {
        router.delete(route('staff-invoices.destroy', props.invoice.id), {
            onSuccess: () => {
                router.visit(route('staff-invoices.index'));
            },
            onError: (errors) => {
                if (errors.error) alert(errors.error);
            },
        });
    }
};

const downloadPdf = () => {
    window.location.href = route('staff-invoices.pdf', props.invoice.id);
};

const submitPaymentDueDate = () => {
    paymentDueDateForm.post(route('staff-invoices.update-payment-due-date', props.invoice.id), {
        onSuccess: () => {
            paymentDueDateForm.reset();
            paymentDueDateForm.payment_due_date = getEffectiveDueDateYmd(props.invoice);
        },
    });
};

// Invoice.vueに渡すためのデータを準備
// スタッフ請求書の場合、client（請求先）は株式会社平田工業、issuer（発行元）はスタッフ情報
const invoiceClient = computed(() => ({
    name: '株式会社平田工業',
    postal: '710-1313',
    address: '岡山県倉敷市真備町川辺233-1',
}));

const invoiceIssuer = computed(() => ({
    name: props.invoice.staff?.name || '',
    postal: props.invoice.staff?.postal_code || '',
    address1: props.invoice.staff?.address || '',
    address2: '',
    tel: props.invoice.staff?.phone || '',
    person: '',
}));

const invoiceData = computed(() => {
    const issueDate = props.invoice.issue_date ? formatDate(props.invoice.issue_date) : '';
    const dueDateRaw = props.invoice.payment_due_date
        ? parseLocalDate(props.invoice.payment_due_date)
        : (() => {
            const issue = parseLocalDate(props.invoice.issue_date);
            if (!issue) return null;
            issue.setDate(issue.getDate() + 30);
            return issue;
        })();
    const dueDate = dueDateRaw ? formatDate(dueDateRaw) : '';
    return {
        issueDate: issueDate,
        number: props.invoice.invoice_number || '',
        dueDate: dueDate,
        title: `請求期間: ${formatDate(props.invoice.period_from)} ～ ${formatDate(props.invoice.period_to)}`,
        bank: props.invoice.staff?.bank_name 
            ? `${props.invoice.staff.bank_name} ${props.invoice.staff.branch_name || ''} ${props.invoice.staff.account_type || ''} ${props.invoice.staff.account_number || ''}`.trim()
            : '',
        remarks: '',
        total: props.invoice.total || 0,
        subtotal: props.invoice.subtotal || 0,
        tax: props.invoice.tax || 0,
    };
});

// 作業実績データをInvoice.vueの形式に変換（請求書はスナップショットの単価・金額を使用し単価マスタ変更の影響を受けない）
const workItems = computed(() => {
    if (!props.invoice.details) return [];

    return props.invoice.details.map((detail) => {
        const workRecord = detail.work_record;
        const drawing = workRecord?.drawing;
        const client = drawing?.client;

        // 日付を取得（作業開始日）
        const date = workRecord?.start_time
            ? formatDate(workRecord.start_time)
            : '';

        // 客先名
        const clientName = client?.name || '';

        // 品番（図番）
        const drawingNumber = drawing?.drawing_number || '';

        // 品名
        const productName = drawing?.product_name || '';

        // 実績数（良品数 + 不良数）
        const quantity = (workRecord?.quantity_good || 0) + (workRecord?.quantity_ng || 0);

        // 1個あたりの重量（kg）
        const weightPerUnit = drawing?.weight_per_unit || 0;

        // 請求書作成時に保存した単価・金額のスナップショットを使用（単価マスタ変更の影響を受けない）
        const unitPrice = detail.unit_price != null && detail.amount != null
            ? weightPerUnit * Number(detail.unit_price)
            : 0;
        const amount = detail.amount != null ? Number(detail.amount) : 0;

        return {
            date,
            client: clientName,
            drawingNumber,
            productName,
            quantity,
            unitPrice,
            amount,
            rateTooltip: detail.rate_tooltip || null,
        };
    });
});
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
                        @click="deleteInvoice"
                        class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                    >
                        削除
                    </button>
                    <button
                        v-if="invoice.status === 'draft'"
                        @click="fixInvoice"
                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                    >
                        確定
                    </button>
                    <template v-if="invoice.status === 'fixed' || invoice.status === 'paid'">
                        <button
                            @click="revertToDraft"
                            class="bg-amber-500 hover:bg-amber-700 text-white font-bold py-2 px-4 rounded"
                        >
                            下書きに戻す
                        </button>
                        <button
                            @click="deleteInvoice"
                            class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                        >
                            削除
                        </button>
                        <button
                            @click="downloadPdf"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                        >
                            PDF出力
                        </button>
                    </template>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <p class="text-sm text-gray-600">
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

                <!-- 下書き時のみ: 支払い期限の変更 -->
                <div
                    v-if="invoice.status === 'draft'"
                    class="mb-6 bg-white shadow-sm rounded-lg p-6 border border-gray-200"
                >
                    <h3 class="text-lg font-bold mb-4">支払い期限</h3>
                    <form @submit.prevent="submitPaymentDueDate" class="flex flex-wrap items-end gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">支払い期限</label>
                            <input
                                v-model="paymentDueDateForm.payment_due_date"
                                type="date"
                                class="rounded-md border-gray-300 shadow-sm"
                                :class="{ 'border-red-500': paymentDueDateForm.errors.payment_due_date }"
                            />
                            <p v-if="paymentDueDateForm.errors.payment_due_date" class="mt-1 text-sm text-red-600">
                                {{ paymentDueDateForm.errors.payment_due_date }}
                            </p>
                        </div>
                        <button
                            type="submit"
                            :disabled="paymentDueDateForm.processing"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                        >
                            {{ paymentDueDateForm.processing ? '保存中...' : '支払い期限を保存' }}
                        </button>
                    </form>
                </div>
                
                <Invoice
                    type="staff"
                    :client="invoiceClient"
                    :issuer="invoiceIssuer"
                    :invoice="invoiceData"
                    :work-items="workItems"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>

