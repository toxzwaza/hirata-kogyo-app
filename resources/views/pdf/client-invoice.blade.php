<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
        }
        body {
            font-size: 11pt;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .invoice {
            border-top: 4px solid #2e7d32;
            color: #000;
            padding: 10mm 12mm;
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            box-sizing: border-box;
        }

        .header {
            margin-bottom: 6px;
        }

        .header h1 {
            text-align: center;
            letter-spacing: 0.3em;
            margin: 6px 0;
            font-size: 20pt;
            font-weight: bold;
        }

        .top {
            display: table;
            width: 100%;
            margin-bottom: 6px;
        }

        .client {
            display: table-cell;
            width: 55%;
            font-size: 12pt;
            vertical-align: top;
        }

        .client-name {
            font-weight: bold;
            margin-bottom: 2px;
            font-size: 14pt;
        }

        .client p {
            margin: 1px 0;
            font-size: 12pt;
        }

        .meta {
            display: table-cell;
            width: 45%;
            vertical-align: top;
            text-align: right;
        }

        .meta table {
            border-collapse: collapse;
            font-size: 11pt;
            width: 100%;
            margin-left: auto;
        }

        .meta th,
        .meta td {
            border: 1px solid #000;
            padding: 3px 6px;
            font-size: 11pt;
        }

        .meta th {
            background: #f5f5f5;
            width: 80px;
        }

        .message {
            margin: 6px 0;
            font-size: 12pt;
        }

        .summary {
            width: 50%;
            margin-bottom: 6px;
            font-size: 11pt;
        }

        .summary table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary th,
        .summary td {
            border: 1px solid #000;
            padding: 3px;
            font-size: 11pt;
        }

        .summary th {
            background: #f5f5f5;
            width: 80px;
        }

        .issuer {
            text-align: right;
            margin-top: -60px;
            font-size: 11pt;
            position: relative;
        }

        .issuer-name {
            font-weight: bold;
            font-size: 14pt;
            margin-bottom: 1px;
        }

        .issuer p {
            margin: 0.5px 0;
            font-size: 11pt;
        }

        .total-box {
            display: table;
            width: 280px;
            border: 2px solid #000;
            padding: 6px;
            margin: 8px 0;
            font-size: 16pt;
            font-weight: bold;
        }

        .total-box span {
            display: table-cell;
        }

        .total-amount {
            text-align: right;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 11pt;
            table-layout: fixed;
        }

        .items th,
        .items td {
            border: 1px solid #000;
            padding: 4px;
            font-size: 11pt;
            word-wrap: break-word;
        }

        .items th {
            background: #333;
            color: #fff;
            padding: 5px 4px;
            text-align: center;
            font-weight: bold;
        }

        .items td {
            padding: 3px 4px;
            vertical-align: middle;
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

        .number {
            text-align: right;
        }

        .amount {
            text-align: right;
        }

        .footer {
            margin-top: 6px;
            font-size: 11pt;
        }

        .footer p {
            margin: 2px 0;
        }

        .remarks {
            margin-top: 8px;
        }

        .remarks h3 {
            background: #333;
            color: #fff;
            padding: 3px;
            font-size: 12pt;
            margin: 0;
            font-weight: bold;
        }

        .remarks-box {
            border: 1px dashed #000;
            min-height: 45px;
            padding: 5px;
            white-space: pre-wrap;
            font-size: 11pt;
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
</head>
<body>
    <div class="invoice">
        <!-- ヘッダー -->
        <div class="header">
            <h1>請 求 書</h1>
        </div>

        <!-- 上部 -->
        <div class="top">
            <!-- 請求先 -->
            <div class="client">
                <p class="client-name">{{ $clientName }} 御中</p>
                @if($clientPostal)
                    <p>〒{{ $clientPostal }}</p>
                @endif
                @if($clientAddress)
                    <p>{{ $clientAddress }}</p>
                @endif
            </div>

            <!-- 発行情報 -->
            <div class="meta">
                <table>
                    <tr>
                        <th>発行日</th>
                        <td>{{ $invoice->issue_date ? $invoice->issue_date->format('Y年n月j日') : '' }}</td>
                    </tr>
                    <tr>
                        <th>請求書No.</th>
                        <td>{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <th>支払期限</th>
                        <td>{{ $invoice->issue_date ? $invoice->issue_date->copy()->addDays(30)->format('Y年n月j日') : '' }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <p class="message">下記のとおり、御請求申し上げます。</p>

        <!-- 概要 -->
        <div class="summary">
            <table>
                <tr>
                    <th>件名</th>
                    <td>@if($type === 'client')請求期間: {{ $invoice->period_from->format('Y年n月j日') }} ～ {{ $invoice->period_to->format('Y年n月j日') }}@else{{ $title ?? ($clientName . ' 様 請求内訳') }}@endif</td>
                </tr>
            </table>
        </div>

        <!-- 事業者 -->
        <div class="issuer">
            <p class="issuer-name">株式会社○○</p>
            <p>〒710-1313</p>
            <p>岡山県倉敷市真備町川辺233-1</p>
            <p>TEL：080-8071-0566</p>
            <p>担当：平田 敦士</p>
        </div>

        <!-- 一枚目: 客先請求書（スタッフごとの集約） -->
        @if($type === 'client')
            <!-- 合計 -->
            <div class="total-box">
                <span>合計</span>
                <span class="total-amount">¥{{ number_format($invoice->total, 0) }}</span>
            </div>

            <!-- 明細 -->
            <table class="items client">
                <thead>
                    <tr>
                        <th>日付</th>
                        <th>項目名</th>
                        <th class="amount">金額</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clientItems as $item)
                        <tr>
                            <td>{{ $item['date'] }}</td>
                            <td>{{ $item['name'] }}</td>
                            <td class="amount">¥{{ number_format($item['amount']) }}</td>
                        </tr>
                    @endforeach
                    <!-- 小計・消費税・合計行 -->
                    @if(count($clientItems) > 0)
                        <tr class="summary-row">
                            <td colspan="2" class="text-right">小計</td>
                            <td class="amount">¥{{ number_format($invoice->subtotal, 0) }}</td>
                        </tr>
                        <tr class="summary-row">
                            <td colspan="2" class="text-right">消費税(10%)</td>
                            <td class="amount">¥{{ number_format($invoice->tax, 0) }}</td>
                        </tr>
                        @if($invoice->adjustment_amount != 0)
                            <tr class="summary-row">
                                <td colspan="2" class="text-right">差額調整</td>
                                <td class="amount">¥{{ number_format($invoice->adjustment_amount, 0) }}</td>
                            </tr>
                        @endif
                        <tr class="summary-row">
                            <td colspan="2" class="text-right">合計</td>
                            <td class="amount">¥{{ number_format($invoice->total, 0) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>

            <!-- 注意書き -->
            <div class="footer">
                <p>当社は消費税の免税事業者です。本請求書には消費税額を明記しておりません。</p>
                <p>振込手数料は貴社にてご負担をお願いいたします。</p>
            </div>

            <!-- 備考 -->
            <div class="remarks">
                <h3>備考</h3>
                <div class="remarks-box">
                    {{ $invoice->adjustment_reason ?? ' ' }}
                </div>
            </div>

        <!-- 二枚目以降: スタッフごとの内訳 -->
        @elseif($type === 'client-detail')
            <!-- 合計 -->
            <div class="total-box">
                <span>合計</span>
                <span class="total-amount">¥{{ number_format($totalAmount, 0) }}</span>
            </div>

            <!-- 明細 -->
            <table class="items client-detail">
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
                    @foreach($workItems as $item)
                        <tr>
                            <td>{{ $item['date'] }}</td>
                            <td>{{ $item['client'] }}</td>
                            <td>{{ $item['drawingNumber'] }}</td>
                            <td>{{ $item['productName'] }}</td>
                            <td class="number">{{ number_format($item['quantity']) }}</td>
                            <td class="number">{{ number_format($item['weightPerUnit'], 2) }}kg</td>
                            <td class="number">{{ number_format($item['totalWeight'], 2) }}kg</td>
                            <td class="number">¥{{ number_format($item['unitPrice']) }}</td>
                            <td class="amount">¥{{ number_format($item['amount']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- 注意書き -->
            <div class="footer">
                <p>当社は消費税の免税事業者です。本請求書には消費税額を明記しておりません。</p>
                <p>振込手数料は貴社にてご負担をお願いいたします。</p>
            </div>
        @endif
    </div>
</body>
</html>
