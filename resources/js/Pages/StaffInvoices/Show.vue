<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import Invoice from '@/Components/Invoice.vue';

const props = defineProps({
    invoice: Object,
});

const formatDate = (date) => {
    if (!date) return '';
    const d = new Date(date);
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

const deleteInvoice = () => {
    if (confirm('この請求書を削除しますか？この操作は取り消せません。')) {
        router.delete(route('staff-invoices.destroy', props.invoice.id), {
            onSuccess: () => {
                router.visit(route('staff-invoices.index'));
            },
        });
    }
};

const downloadPdf = () => {
    window.location.href = route('staff-invoices.pdf', props.invoice.id);
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
    const dueDate = props.invoice.issue_date 
        ? formatDate(new Date(new Date(props.invoice.issue_date).getTime() + 30 * 24 * 60 * 60 * 1000))
        : '';
    
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

// 作業実績データをInvoice.vueの形式に変換
const workItems = computed(() => {
    if (!props.invoice.details) return [];
    
    return props.invoice.details.map((detail) => {
        const workRecord = detail.work_record;
        const drawing = workRecord?.drawing;
        const client = drawing?.client;
        const workRate = workRecord?.work_rate;
        
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
        
        // 作業開始時間をチェックして、残業単価を使用するか判定
        let rateToUse = workRate?.rate_contractor || 0;
        let reason = '定時';
        
        if (workRecord?.start_time) {
            const startTime = new Date(workRecord.start_time);
            const hours = startTime.getHours();
            const minutes = startTime.getMinutes();
            const isOvertime = hours > 17 || (hours === 17 && minutes >= 10);
            
            if (isOvertime) {
                if (workRate?.rate_overtime != null) {
                    rateToUse = workRate.rate_overtime;
                    reason = '残業';
                } else {
                    reason = '残業だがrate_overtimeがNULL';
                }
            }
        }
        
        // 使用した単価と理由をコンソールに出力
        console.log(`[作業実績] 品番: ${drawingNumber}, 開始時間: ${workRecord?.start_time || '不明'}`);
        console.log(`  使用単価: ${rateToUse}円/kg (理由: ${reason})`);
        console.log(`  rate_contractor: ${workRate?.rate_contractor || 'NULL'}, rate_overtime: ${workRate?.rate_overtime ?? 'NULL'}`);
        
        // 個単価（1個あたりの重量 × 単価）
        // 単価は重量あたりの単価（円/kg）なので、1個あたりの単価を計算
        const unitPrice = weightPerUnit * rateToUse;
        
        // 金額（実績数 × 個単価）
        const amount = quantity * unitPrice;
        
        return {
            date,
            client: clientName,
            drawingNumber,
            productName,
            quantity,
            unitPrice,
            amount,
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
                    <button
                        v-if="invoice.status === 'fixed' || invoice.status === 'paid'"
                        @click="downloadPdf"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                    >
                        PDF出力
                    </button>
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

