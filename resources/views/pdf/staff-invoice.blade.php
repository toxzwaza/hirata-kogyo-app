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

        /* スタッフ請求書（7列）の列幅設定 */
        .items th:nth-child(1),
        .items td:nth-child(1) {
            width: 12%;
        }

        .items th:nth-child(2),
        .items td:nth-child(2) {
            width: 15%;
        }

        .items th:nth-child(3),
        .items td:nth-child(3) {
            width: 12%;
        }

        .items th:nth-child(4),
        .items td:nth-child(4) {
            width: 20%;
        }

        .items th:nth-child(5),
        .items td:nth-child(5) {
            width: 12%;
        }

        .items th:nth-child(6),
        .items td:nth-child(6) {
            width: 12%;
        }

        .items th:nth-child(7),
        .items td:nth-child(7) {
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
            border: 1px solid #000;
            min-height: 45px;
            padding: 5px;
            white-space: pre-wrap;
            font-size: 11pt;
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
                <p class="client-name">株式会社平田工業 御中</p>
                <p>〒710-1313</p>
                <p>岡山県倉敷市真備町川辺233-1</p>
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
                    <td>請求期間: {{ $invoice->period_from->format('Y年n月j日') }} ～ {{ $invoice->period_to->format('Y年n月j日') }}</td>
                </tr>
                @if($invoice->staff->bank_name)
                    <tr>
                        <th>振込先</th>
                        <td>{{ $invoice->staff->bank_name }} {{ $invoice->staff->branch_name ?? '' }} {{ $invoice->staff->account_type ?? '' }} {{ $invoice->staff->account_number ?? '' }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <!-- 事業者 -->
        <div class="issuer">
            <p class="issuer-name">{{ $invoice->staff->name }}</p>
            @if($invoice->staff->postal_code)
                <p>〒{{ $invoice->staff->postal_code }}</p>
            @endif
            @if($invoice->staff->address)
                <p>{{ $invoice->staff->address }}</p>
            @endif
            @if($invoice->staff->phone)
                <p>TEL：{{ $invoice->staff->phone }}</p>
            @endif
        </div>

        <!-- 合計 -->
        <div class="total-box">
            <span>合計</span>
            <span class="total-amount">¥{{ number_format($invoice->total, 0) }}</span>
        </div>

        <!-- 明細 -->
        <table class="items">
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
                @foreach($workItems as $item)
                    <tr>
                        <td>{{ $item['date'] }}</td>
                        <td>{{ $item['client'] }}</td>
                        <td>{{ $item['drawingNumber'] }}</td>
                        <td>{{ $item['productName'] }}</td>
                        <td class="number">{{ number_format($item['quantity']) }}</td>
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

        <!-- 備考 -->
        <div class="remarks">
            <h3>備考</h3>
            <div class="remarks-box">
                {{ $invoice->remarks ?? ' ' }}
            </div>
        </div>
    </div>
</body>
</html>
