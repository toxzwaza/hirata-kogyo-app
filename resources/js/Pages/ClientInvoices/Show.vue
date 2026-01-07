<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import Invoice from "@/Components/Invoice.vue";

const props = defineProps({
  invoice: Object,
});

const showAdjustmentForm = ref(false);

const adjustmentForm = useForm({
  adjustment_amount: props.invoice.adjustment_amount || 0,
  adjustment_reason: props.invoice.adjustment_reason || "",
});

const formatDate = (date) => {
  if (!date) return "";
  const d = new Date(date);
  const year = d.getFullYear();
  const month = d.getMonth() + 1;
  const day = d.getDate();
  return `${year}年${month}月${day}日`;
};

const formatNumber = (num) => {
  return new Intl.NumberFormat("ja-JP").format(num);
};

const fixInvoice = () => {
  if (confirm("この請求書を確定しますか？確定後は編集できません。")) {
    router.post(route("client-invoices.fix", props.invoice.id));
  }
};

const deleteInvoice = () => {
  if (confirm("この請求書を削除しますか？この操作は取り消せません。")) {
    router.delete(route("client-invoices.destroy", props.invoice.id), {
      onSuccess: () => {
        router.visit(route("client-invoices.index"));
      },
    });
  }
};

const submitAdjustment = () => {
  adjustmentForm.post(
    route("client-invoices.update-adjustment", props.invoice.id),
    {
      onSuccess: () => {
        showAdjustmentForm = false;
      },
    }
  );
};

const downloadPdf = () => {
  window.location.href = route("client-invoices.pdf", props.invoice.id);
};

// Invoice.vueに渡すためのデータを準備
const invoiceClient = computed(() => ({
  name: "株式会社アキオカ",
  postal: "",
  address: "",
}));

const invoiceIssuer = computed(() => ({
  name: "株式会社平田工業",
  postal: "710-1313",
  address1: "岡山県倉敷市真備町川辺233-1",
  address2: "",
  tel: "080-8071-0566",
  person: "平田 敦士",
  registrationNumber: "T4260001040131",
  bank: "おかやま信用金庫",
  bankBranch: "妹尾支店　店番170",
  bankAccount: "口座番号　0600264",
}));

const invoiceData = computed(() => {
  const issueDate = props.invoice.issue_date
    ? formatDate(props.invoice.issue_date)
    : "";
  const dueDate = props.invoice.issue_date
    ? formatDate(
        new Date(
          new Date(props.invoice.issue_date).getTime() +
            30 * 24 * 60 * 60 * 1000
        )
      )
    : "";

  return {
    issueDate: issueDate,
    number: props.invoice.invoice_number || "",
    dueDate: dueDate,
    title: `請求期間: ${formatDate(props.invoice.period_from)} ～ ${formatDate(
      props.invoice.period_to
    )}`,
    bank: "",
    remarks: props.invoice.adjustment_reason || "",
  };
});

// スタッフごとの合計金額を計算（二枚目以降の合計を四捨五入した値）
const staffTotals = computed(() => {
  const totals = {};
  
  if (props.invoice.staff_invoice_items) {
    props.invoice.staff_invoice_items.forEach((item) => {
      const staffInvoice = item.staff_invoice;
      const staffName = staffInvoice?.staff?.name || "";
      
      if (!totals[staffName]) {
        // そのスタッフの作業実績の合計を計算
        const details = staffInvoice?.details || [];
        let sum = 0;
        
        details.forEach((detail) => {
          const workRecord = detail.work_record;
          const drawing = workRecord?.drawing;
          const workRate = workRecord?.work_rate;
          
          const quantity =
            (workRecord?.quantity_good || 0) + (workRecord?.quantity_ng || 0);
          const weightPerUnit = drawing?.weight_per_unit || 0;
          const totalWeight = quantity * weightPerUnit;
          const unitPrice = workRate?.rate_employee || 0;
          const amount = totalWeight * unitPrice;
          
          sum += amount;
        });
        
        // 小数点第一位で四捨五入して整数にする
        totals[staffName] = Math.round(sum);
      }
    });
  }
  
  return totals;
});

// 一枚目の客先請求書用アイテム（スタッフごとの集約）
const clientItems = computed(() => {
  if (!props.invoice.staff_invoice_items) return [];

  return props.invoice.staff_invoice_items.map((item) => {
    const staffInvoice = item.staff_invoice;
    const staff = staffInvoice?.staff;

    // 日付（期間表記: 〇年〇月〇日~〇年〇月〇日）
    const periodFrom = staffInvoice?.period_from
      ? formatDate(staffInvoice.period_from)
      : "";
    const periodTo = staffInvoice?.period_to
      ? formatDate(staffInvoice.period_to)
      : "";
    const date = periodFrom && periodTo ? `${periodFrom} ～ ${periodTo}` : "";

    // 項目名（仕上げ加工 氏名）
    const workMethodName = "仕上げ加工"; // 作業方法名を取得（必要に応じて調整）
    const staffName = staff?.name || "";
    const name = `${workMethodName} ${staffName}`;

    // 金額（二枚目以降の合計を四捨五入した値を使用）
    const amount = staffTotals.value[staffName] || 0;

    return {
      date,
      name,
      amount,
    };
  });
});

// スタッフごとの作業実績データ（二枚目以降用）
const staffWorkItems = computed(() => {
  if (!props.invoice.staff_invoice_items) return [];

  const allItems = [];

  props.invoice.staff_invoice_items.forEach((item) => {
    const staffInvoice = item.staff_invoice;
    const details = staffInvoice?.details || [];

    details.forEach((detail) => {
      const workRecord = detail.work_record;
      const drawing = workRecord?.drawing;
      const client = drawing?.client;
      const workRate = workRecord?.work_rate;

      // 日付を取得（作業開始日）
      const date = workRecord?.start_time
        ? formatDate(workRecord.start_time)
        : "";

      // 客先名
      const clientName = client?.name || "";

      // 品番（図番）
      const drawingNumber = drawing?.drawing_number || "";

      // 品名
      const productName = drawing?.product_name || "";

      // 実績数（良品数 + 不良数）
      const quantity =
        (workRecord?.quantity_good || 0) + (workRecord?.quantity_ng || 0);

      // 重量（1個あたりの重量）
      const weightPerUnit = drawing?.weight_per_unit || 0;

      // 総重量（kg）
      const totalWeight = quantity * weightPerUnit;

      // 単価（rate_employeeを使用）
      const unitPrice = workRate?.rate_employee || 0;

      // 金額（総重量 × 単価）
      const amount = totalWeight * unitPrice;

      allItems.push({
        date,
        client: clientName,
        drawingNumber,
        productName,
        quantity,
        weightPerUnit,
        totalWeight,
        unitPrice,
        amount,
        staffName: staffInvoice?.staff?.name || "",
      });
    });
  });

  return allItems;
});

// スタッフごとにグループ化
const groupedStaffItems = computed(() => {
  const grouped = {};

  staffWorkItems.value.forEach((item) => {
    const staffName = item.staffName;
    if (!grouped[staffName]) {
      grouped[staffName] = [];
    }
    grouped[staffName].push(item);
  });

  return grouped;
});
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
            v-if="invoice.status === 'draft'"
            @click="deleteInvoice"
            class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
          >
            削除
          </button>
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
            v-if="invoice.status === 'fixed' || invoice.status === 'issued'"
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
        <!-- 差額調整フォーム -->
        <div
          v-if="showAdjustmentForm && invoice.status === 'draft'"
          class="mb-6 bg-yellow-50 p-4 rounded-lg border border-yellow-200"
        >
          <h3 class="text-lg font-bold mb-4">差額調整</h3>
          <form @submit.prevent="submitAdjustment">
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >差額調整金額 *</label
                >
                <input
                  v-model.number="adjustmentForm.adjustment_amount"
                  type="number"
                  step="0.01"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                  :class="{
                    'border-red-500': adjustmentForm.errors.adjustment_amount,
                  }"
                />
                <p
                  v-if="adjustmentForm.errors.adjustment_amount"
                  class="mt-1 text-sm text-red-600"
                >
                  {{ adjustmentForm.errors.adjustment_amount }}
                </p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >差額調整理由</label
                >
                <input
                  v-model="adjustmentForm.adjustment_reason"
                  type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                  :class="{
                    'border-red-500': adjustmentForm.errors.adjustment_reason,
                  }"
                />
                <p
                  v-if="adjustmentForm.errors.adjustment_reason"
                  class="mt-1 text-sm text-red-600"
                >
                  {{ adjustmentForm.errors.adjustment_reason }}
                </p>
              </div>
              <div class="flex gap-2">
                <button
                  type="submit"
                  :disabled="adjustmentForm.processing"
                  class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                >
                  {{ adjustmentForm.processing ? "更新中..." : "更新" }}
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

        <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
          <div class="flex justify-between items-center mb-4">
            <p class="text-sm text-gray-600">
              ステータス:
              <span
                :class="{
                  'text-gray-600': invoice.status === 'draft',
                  'text-blue-600': invoice.status === 'fixed',
                  'text-green-600': invoice.status === 'issued',
                }"
                class="font-bold"
              >
                {{
                  invoice.status === "draft"
                    ? "下書き"
                    : invoice.status === "fixed"
                    ? "確定"
                    : "発行済"
                }}
              </span>
            </p>
          </div>
        </div>

        <!-- 一枚目: 客先請求書 -->
        <Invoice
          type="client"
          :client="invoiceClient"
          :issuer="invoiceIssuer"
          :invoice="{
            ...invoiceData,
            subtotal: invoice.subtotal,
            tax: invoice.tax,
            adjustment_amount: invoice.adjustment_amount,
            total: invoice.total,
          }"
          :client-items="clientItems"
        />

        <!-- 二枚目以降: スタッフごとの内訳 -->
        <div
          v-for="(items, staffName) in groupedStaffItems"
          :key="staffName"
          class="mt-8"
        >
          <Invoice
            type="client-detail"
            :client="{ name: staffName, postal: '', address: '' }"
            :issuer="invoiceIssuer"
            :invoice="{
              ...invoiceData,
              title: `${staffName} 様 請求内訳`,
            }"
            :work-items="items"
          />
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

