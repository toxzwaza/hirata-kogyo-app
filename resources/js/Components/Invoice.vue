<template>
  <div class="invoice">
    <!-- ヘッダー -->
    <div class="header">
      <h1>請 求 書</h1>
    </div>

    <!-- 上部 -->
    <div class="top">
      <!-- 請求先 -->
      <div class="client">
        <p class="client-name" v-if="type === 'client'">株式会社アキオカ 御中</p>
        <p class="client-name" v-else-if="type === 'client-detail'">株式会社平田工業 {{ client.name }}</p>
        <p class="client-name" v-else-if="type === 'staff'">{{ client.name }} 御中</p>
        <p class="client-name" v-else>{{ client.name }} 御中</p>
        <p v-if="client.postal">〒{{ client.postal }}</p>
        <p v-if="client.address">{{ client.address }}</p>
      </div>

      <!-- 発行情報 -->
      <div class="meta">
        <table>
          <tr>
            <th>発行日</th>
            <td>{{ invoice.issueDate }}</td>
          </tr>
          <tr v-if="type !== 'client-detail'">
            <th>請求書No.</th>
            <td>{{ invoice.number }}</td>
          </tr>
          <tr v-if="type !== 'client-detail'">
            <th>支払期限</th>
            <td>{{ invoice.dueDate }}</td>
          </tr>
        </table>
      </div>
    </div>
    <p v-if="type !== 'client-detail'" class="message">下記のとおり、御請求申し上げます。</p>

    <!-- 概要 -->
    <div class="summary">
      <table>
        <tr>
          <th>件名</th>
          <td>{{ invoice.title }}</td>
        </tr>
        <tr v-if="type !== 'client-detail' && invoice.bank">
          <th>振込先</th>
          <td>{{ invoice.bank }}</td>
        </tr>
      </table>
    </div>

    <!-- 事業者 -->
    <div v-if="type !== 'client-detail'" class="issuer">
      <p class="issuer-name">{{ issuer.name }}</p>
      <p v-if="issuer.postal">〒{{ issuer.postal }}</p>
      <p v-if="issuer.address1">{{ issuer.address1 }}</p>
      <p v-if="issuer.address2">{{ issuer.address2 }}</p>
      <p v-if="issuer.tel">TEL：{{ issuer.tel }}</p>
      <p v-if="issuer.person">担当：{{ issuer.person }}</p>
      <p v-if="issuer.registrationNumber">登録番号：{{ issuer.registrationNumber }}</p>
      <p v-if="issuer.bank">取引銀行：{{ issuer.bank }}</p>
      <p v-if="issuer.bankBranch">{{ issuer.bankBranch }}</p>
      <p v-if="issuer.bankAccount">{{ issuer.bankAccount }}</p>
      <img v-if="type !== 'staff'" class="issuer-logo" src="/storage/stamp/hanko.png" alt="印鑑" />
    </div>

    <!-- 合計 -->
    <div class="total-box">
      <span>合計</span>
      <span class="total-amount">¥{{ totalAmount.toLocaleString() }}</span>
    </div>

    <!-- 明細 -->
    <!-- スタッフ請求書の場合 -->
    <table v-if="type === 'staff'" :class="['items', tableClass]">
      <thead>
        <tr>
          <th>日付</th>
          <th>客先</th>
          <th>品番</th>
          <th>品名</th>
          <th class="number">実績数</th>
          <th class="number">個単価</th>
          <th class="amount">金額</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, index) in workItems" :key="index">
          <td>{{ item.date }}</td>
          <td>{{ item.client }}</td>
          <td>{{ item.drawingNumber }}</td>
          <td>{{ item.productName }}</td>
          <td class="number">{{ formatNumber(item.quantity) }}</td>
          <td class="number">¥{{ formatNumber(item.unitPrice) }}</td>
          <td class="amount">¥{{ formatNumber(item.amount) }}</td>
        </tr>
      </tbody>
    </table>

    <!-- 客先請求書（一枚目）の場合 -->
    <table v-else-if="type === 'client'" :class="['items', tableClass]">
      <thead>
        <tr>
          <th>日付</th>
          <th>項目名</th>
          <th class="amount">金額</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, index) in clientItems" :key="index">
          <td>{{ item.date }}</td>
          <td>{{ item.name }}</td>
          <td class="amount">¥{{ formatNumber(item.amount) }}</td>
        </tr>
        <!-- 小計・消費税・合計行 -->
        <tr v-if="clientItems.length > 0" class="summary-row">
          <td colspan="2" class="text-right font-bold">小計</td>
          <td class="amount font-bold">¥{{ formatNumber(subtotalAmount) }}</td>
        </tr>
        <tr v-if="clientItems.length > 0" class="summary-row">
          <td colspan="2" class="text-right font-bold">消費税(10%)</td>
          <td class="amount font-bold">¥{{ formatNumber(taxAmount) }}</td>
        </tr>
        <tr v-if="clientItems.length > 0" class="summary-row">
          <td colspan="2" class="text-right font-bold">合計</td>
          <td class="amount font-bold">¥{{ formatNumber(totalAmountWithTax) }}</td>
        </tr>
      </tbody>
    </table>

    <!-- 客先請求書（スタッフごとの内訳）の場合 -->
    <table v-else-if="type === 'client-detail'" :class="['items', tableClass]">
      <thead>
        <tr>
          <th>日付</th>
          <th>客先</th>
          <th>品番</th>
          <th>品名</th>
          <th class="number">実績数</th>
          <th class="number">重量</th>
          <th class="number">総重量</th>
          <th class="number">単価</th>
          <th class="amount">金額</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, index) in workItems" :key="index">
          <td>{{ item.date }}</td>
          <td>{{ item.client }}</td>
          <td>{{ item.drawingNumber }}</td>
          <td>{{ item.productName }}</td>
          <td class="number">{{ formatNumber(item.quantity) }}</td>
          <td class="number">{{ formatNumber(item.weightPerUnit) }}kg</td>
          <td class="number">{{ formatNumber(item.totalWeight) }}kg</td>
          <td class="number">¥{{ formatNumber(item.unitPrice) }}</td>
          <td class="amount">¥{{ formatNumber(item.amount) }}</td>
        </tr>
      </tbody>
    </table>

    <!-- 注意書き -->
    <div class="footer">
      <p>
        当社は消費税の免税事業者です。本請求書には消費税額を明記しておりません。
      </p>
      <p>振込手数料は貴社にてご負担をお願いいたします。</p>
    </div>

    <!-- 備考 -->
    <div class="remarks">
      <h3>備考</h3>
      <div class="remarks-box">
        {{ invoice.remarks || ' ' }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  type: {
    type: String,
    required: true,
    validator: (value) => ['staff', 'client', 'client-detail'].includes(value),
  },
  client: Object,
  issuer: Object,
  invoice: Object,
  workItems: {
    type: Array,
    default: () => [],
  },
  clientItems: {
    type: Array,
    default: () => [],
  },
});

const formatNumber = (num) => {
  return new Intl.NumberFormat('ja-JP').format(num || 0);
};

const totalAmount = computed(() => {
  if (props.type === 'staff') {
    // スタッフ請求書の場合は、workItemsから各行の金額を合計
    // 各行の金額は正しく計算されているため、その合計を使用
    const sum = props.workItems.reduce((sum, item) => sum + Number(item.amount || 0), 0);
    return Math.round(sum);
  } else if (props.type === 'client-detail') {
    // 二枚目以降の合計を小数点第一位で四捨五入して整数にする
    const sum = props.workItems.reduce((sum, item) => sum + Number(item.amount || 0), 0);
    return Math.round(sum);
  } else if (props.type === 'client') {
    // 客先請求書の場合は、小計 + 消費税 + 差額調整を返す
    return totalAmountWithTax.value;
  }
  return 0;
});

// 客先請求書の小計（税抜き）- clientItemsの合計（四捨五入済み）から計算
const subtotalAmount = computed(() => {
  if (props.type === 'client') {
    return props.clientItems.reduce((sum, item) => sum + Number(item.amount || 0), 0);
  }
  return 0;
});

// 客先請求書の消費税（小計から計算、10%）
const taxAmount = computed(() => {
  if (props.type === 'client') {
    const subtotal = subtotalAmount.value;
    // 消費税を計算（10%）、小数点以下切り捨て
    return Math.floor(subtotal * 0.1);
  }
  return 0;
});

// 客先請求書の合計（小計 + 消費税 + 差額調整）
const totalAmountWithTax = computed(() => {
  if (props.type === 'client') {
    const subtotal = Number(subtotalAmount.value) || 0;
    const tax = Number(taxAmount.value) || 0;
    const adjustment = Number(props.invoice?.adjustment_amount) || 0;
    return subtotal + tax + adjustment;
  }
  return 0;
});

// アイテム数を取得
const itemCount = computed(() => {
  if (props.type === 'staff' || props.type === 'client-detail') {
    return props.workItems.length;
  } else if (props.type === 'client') {
    return props.clientItems.length;
  }
  return 0;
});

// 行数に応じたスタイルクラスを計算
// 行数が多い場合は複数ページにまたがることを許可
const tableClass = computed(() => {
  const count = itemCount.value;
  const typeClass = `items-${props.type}`;
  
  let sizeClass = '';
  if (count === 0) {
    sizeClass = 'items-empty';
  } else if (count <= 5) {
    sizeClass = 'items-small';
  } else if (count <= 10) {
    sizeClass = 'items-medium';
  } else if (count <= 15) {
    sizeClass = 'items-large';
  } else if (count <= 30) {
    // 15-30行は中サイズ
    sizeClass = 'items-extra-large';
  } else {
    // 30行を超える場合は標準サイズで複数ページに分ける
    sizeClass = 'items-multi-page';
  }
  
  return `${typeClass} ${sizeClass}`;
});
</script>

<style scoped>
@page {
  size: A4;
  margin: 15mm;
}

.invoice {
  font-family: "Hiragino Kaku Gothic ProN", "Meiryo", sans-serif;
  border-top: 4px solid #2e7d32;
  color: #000;
  padding: 10mm 12mm;
  max-width: 210mm;
  min-height: 267mm; /* A4高さ(297mm) - 上下マージン(30mm) = 267mm */
  margin: 0 auto;
  background: white;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  page-break-after: auto;
  page-break-inside: auto;
}

/* 印刷時に複数ページ対応 */
@media print {
  .invoice {
    min-height: auto;
    page-break-inside: auto;
  }
}

.header {
  flex-shrink: 0;
}

.header h1 {
  text-align: center;
  letter-spacing: 0.3em;
  margin: 6px 0;
  font-size: 20px;
}

.top {
  display: flex;
  justify-content: space-between;
  margin-bottom: 6px;
  flex-shrink: 0;
}

.client {
  width: 55%;
  font-size: 12px;
}

.client-name {
  font-weight: bold;
  margin-bottom: 2px;
  font-size: 14px;
}

.client p {
  margin: 1px 0;
  font-size: 12px;
}

.meta table {
  border-collapse: collapse;
  font-size: 11px;
}

.meta th,
.meta td {
  border: 1px solid #000;
  padding: 3px 6px;
  font-size: 11px;
}

.meta th {
  background: #f5f5f5;
  width: 80px;
}

.message {
  margin: 6px 0;
  font-size: 12px;
  flex-shrink: 0;
}

.summary {
  width: 50%;
  margin-bottom: 6px;
  font-size: 11px;
  flex-shrink: 0;
}

.summary table {
  width: 100%;
  border-collapse: collapse;
}

.summary th,
.summary td {
  border: 1px solid #000;
  padding: 3px;
  font-size: 11px;
}

.summary th {
  background: #f5f5f5;
  width: 80px;
}

.issuer {
  text-align: right;
  margin-top: -60px;
  font-size: 11px;
  flex-shrink: 0;
  position: relative;
}

.issuer-logo {
  position: absolute;
  right: -15px;
  bottom: 0;
  width: 24px;
}

.issuer-name {
  font-weight: bold;
  font-size: 14px;
  margin-bottom: 1px;
}

.issuer p {
  margin: 0.5px 0;
  font-size: 11px;
}

.total-box {
  display: flex;
  justify-content: space-between;
  width: 280px;
  border: 2px solid #000;
  padding: 6px;
  margin: 8px 0;
  font-size: 16px;
  font-weight: bold;
  flex-shrink: 0;
}

.items {
  width: 100%;
  max-width: 100%;
  border-collapse: collapse;
  margin-top: 6px;
  font-size: 11px;
  /* flex: 1; */
  min-height: 0;
  table-layout: fixed;
  page-break-inside: auto;
}

.items thead {
  display: table-header-group;
  /* 各ページでテーブルヘッダーを繰り返し表示 */
}

.items tbody {
  display: table-row-group;
}

.items tbody tr {
  page-break-inside: avoid;
  page-break-after: auto;
}

/* 印刷時にテーブルヘッダーを各ページで繰り返し表示 */
@media print {
  .items thead {
    display: table-header-group;
  }
  
  .items tbody {
    display: table-row-group;
  }
  
  .items tbody tr {
    page-break-inside: avoid;
  }
  
  /* テーブルが複数ページにまたがれるようにする */
  .items {
    page-break-inside: auto;
  }
  
  /* 行を分割しない */
  .items tbody tr {
    page-break-inside: avoid;
  }
}

/* スタッフ請求書（7列）の列幅設定 */
.items.staff th:nth-child(1),
.items.staff td:nth-child(1) {
  width: 12%;
}

.items.staff th:nth-child(2),
.items.staff td:nth-child(2) {
  width: 15%;
}

.items.staff th:nth-child(3),
.items.staff td:nth-child(3) {
  width: 12%;
}

.items.staff th:nth-child(4),
.items.staff td:nth-child(4) {
  width: 20%;
}

.items.staff th:nth-child(5),
.items.staff td:nth-child(5) {
  width: 12%;
}

.items.staff th:nth-child(6),
.items.staff td:nth-child(6) {
  width: 12%;
}

.items.staff th:nth-child(7),
.items.staff td:nth-child(7) {
  width: 17%;
}

/* 客先請求書（3列）の列幅設定 */
.items.client th:nth-child(1),
.items.client td:nth-child(1) {
  width: 35%;
}

.items.client th:nth-child(2),
.items.client td:nth-child(2) {
  width: 48%;
}

.items.client th:nth-child(3),
.items.client td:nth-child(3) {
  width: 17%;
}

/* 客先請求書詳細（9列）の列幅設定 */
.items.client-detail th:nth-child(1),
.items.client-detail td:nth-child(1) {
  width: 10%;
}

.items.client-detail th:nth-child(2),
.items.client-detail td:nth-child(2) {
  width: 12%;
}

.items.client-detail th:nth-child(3),
.items.client-detail td:nth-child(3) {
  width: 10%;
}

.items.client-detail th:nth-child(4),
.items.client-detail td:nth-child(4) {
  width: 15%;
}

.items.client-detail th:nth-child(5),
.items.client-detail td:nth-child(5) {
  width: 9%;
}

.items.client-detail th:nth-child(6),
.items.client-detail td:nth-child(6) {
  width: 9%;
}

.items.client-detail th:nth-child(7),
.items.client-detail td:nth-child(7) {
  width: 9%;
}

.items.client-detail th:nth-child(8),
.items.client-detail td:nth-child(8) {
  width: 9%;
}

.items.client-detail th:nth-child(9),
.items.client-detail td:nth-child(9) {
  width: 17%;
}

.items th,
.items td {
  border: 1px solid #000;
  padding: 4px;
  font-size: 11px;
  word-wrap: break-word;
  overflow: hidden;
}

.items th {
  background: #333;
  color: #fff;
  padding: 5px 4px;
  text-align: center;
}

.items tbody tr {
  height: auto;
  min-height: 24px;
  max-height: 40px;
}

.items td {
  padding: 3px 4px;
  max-height: 40px;
  overflow: hidden;
  text-overflow: ellipsis;
  vertical-align: middle;
}

/* 件数が少ない場合（5行以下）のスタイル */
.items-small {
  /* max-width: 90%; */
  margin-left: auto;
  margin-right: auto;
}

.items-small th,
.items-small td {
  font-size: 11px;
  padding: 6px 4px;
}

.items-small tbody tr {
  min-height: 28px;
  max-height: 35px;
}

.items-small td {
  max-height: 35px;
}

/* 件数が中程度の場合（6-10行）のスタイル */
.items-medium {
  max-width: 100%;
}

.items-medium th,
.items-medium td {
  font-size: 10.5px;
  padding: 4px 3px;
}

.items-medium tbody tr {
  min-height: 22px;
  max-height: 30px;
}

.items-medium td {
  max-height: 30px;
}

/* 件数が多い場合（11-15行）のスタイル */
.items-large {
  max-width: 100%;
}

.items-large th,
.items-large td {
  font-size: 10px;
  padding: 3px 2px;
}

.items-large tbody tr {
  min-height: 20px;
  max-height: 28px;
}

.items-large td {
  max-height: 28px;
}

/* 件数が非常に多い場合（16行以上）のスタイル */
.items-extra-large {
  max-width: 100%;
}

.items-extra-large th,
.items-extra-large td {
  font-size: 9px;
  padding: 2px 2px;
  line-height: 1.2;
}

.items-extra-large tbody tr {
  min-height: 18px;
  max-height: 25px;
}

.items-extra-large td {
  max-height: 25px;
}

.items-empty {
  max-width: 90%;
  margin-left: auto;
  margin-right: auto;
}

/* 30行を超える場合（複数ページ対応）のスタイル */
.items-multi-page {
  max-width: 100%;
}

.items-multi-page th,
.items-multi-page td {
  font-size: 10px;
  padding: 3px 2px;
  line-height: 1.3;
}

.items-multi-page tbody tr {
  min-height: 22px;
  max-height: 26px;
}

.items-multi-page td {
  max-height: 26px;
}

.number {
  text-align: right;
}

.amount {
  text-align: right;
  width: 120px;
  max-width: 15%;
}

.footer {
  margin-top: 6px;
  font-size: 11px;
  flex-shrink: 0;
}

.footer p {
  margin: 2px 0;
}

.remarks {
  margin-top: 8px;
  flex-shrink: 0;
}

.remarks h3 {
  background: #333;
  color: #fff;
  padding: 3px;
  font-size: 12px;
  margin: 0;
}

.remarks-box {
  border: 1px solid #000;
  min-height: 45px;
  padding: 5px;
  white-space: pre-wrap;
  font-size: 11px;
}

.summary-row {
  background: #f5f5f5;
  font-weight: bold;
}

.summary-row td {
  border-top: 2px solid #000;
}

.summary-row td.text-right {
  text-align: right;
}
</style>
