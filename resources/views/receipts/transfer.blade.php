<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>إيصال حوالة - {{ $transfer->transfer_number }}</title>
    <style>
        @font-face {
            font-family: 'Cairo';
            font-style: normal;
            font-weight: 400;
            src: url('https://fonts.gstatic.com/s/cairo/v20/SLXQ1O5wj4oSE1ciQg.ttf') format('truetype');
        }
        body {
            font-family: 'Cairo', 'DejaVu Sans', sans-serif;
            direction: rtl;
            text-align: right;
            padding: 10px;
            font-size: 12px;
            color: #333;
            background-color: #fff;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .logo {
            font-size: 20px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 5px;
        }
        .title {
            font-size: 14px;
            font-weight: bold;
            color: #4b5563;
        }
        .receipt-info {
            margin-bottom: 15px;
            background-color: #f3f4f6;
            padding: 8px;
            border-radius: 4px;
        }
        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 6px;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            color: #4b5563;
            width: 40%;
        }
        .info-value {
            display: table-cell;
            color: #111827;
            text-align: left;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .details-table th, .details-table td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: right;
        }
        .details-table th {
            background-color: #f9fafb;
            font-weight: bold;
            color: #374151;
        }
        .amount-section {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
        }
        .amount-val {
            font-size: 18px;
            font-weight: bold;
            color: #1d4ed8;
            margin-bottom: 4px;
        }
        .amount-words {
            font-size: 11px;
            color: #1e40af;
            font-style: italic;
        }
        .secret-code-section {
            border: 2px dashed #10b981;
            background-color: #ecfdf5;
            padding: 8px;
            border-radius: 4px;
            text-align: center;
            margin-bottom: 15px;
        }
        .secret-code-label {
            font-size: 11px;
            color: #065f46;
            font-weight: bold;
        }
        .secret-code-val {
            font-size: 22px;
            font-weight: bold;
            color: #047857;
            letter-spacing: 4px;
            margin-top: 2px;
        }
        .qr-section {
            text-align: center;
            margin-top: 10px;
        }
        .qr-image {
            width: 90px;
            height: 90px;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
            font-size: 10px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">فودافون حوالات</div>
        <div class="title">إيصال تحويل مالي رسمي</div>
    </div>

    <div class="receipt-info">
        <div class="info-row">
            <span class="info-label">رقم الحوالة:</span>
            <span class="info-value" style="font-family: monospace; font-weight: bold;">{{ $transfer->transfer_number }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">تاريخ التحويل:</span>
            <span class="info-value">{{ $transfer->created_at->format('Y-m-d H:i') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">حالة الحوالة:</span>
            <span class="info-value">
                @if($transfer->status === 'paid')
                    <span style="color: #047857; font-weight: bold;">تم التسليم</span>
                @elseif($transfer->status === 'pending')
                    <span style="color: #d97706; font-weight: bold;">قيد الانتظار</span>
                @else
                    <span style="color: #dc2626; font-weight: bold;">ملغاة</span>
                @endif
            </span>
        </div>
    </div>

    <table class="details-table">
        <thead>
            <tr>
                <th colspan="2">تفاصيل أطراف الحركة</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>المرسل:</strong></td>
                <td>{{ $transfer->sender_name }} ({{ $transfer->sender_phone }})</td>
            </tr>
            <tr>
                <td><strong>المستفيد:</strong></td>
                <td>{{ $transfer->recipient_name }} ({{ $transfer->recipient_phone }})</td>
            </tr>
            <tr>
                <td><strong>الفرع المستلم:</strong></td>
                <td>{{ $transfer->branch ? $transfer->branch->name : 'غير محدد' }}</td>
            </tr>
        </tbody>
    </table>

    <table class="details-table">
        <thead>
            <tr>
                <th colspan="2">العمليات المالية</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>المبلغ المرسل:</td>
                <td style="text-align: left;">{{ number_format((float)$transfer->source_amount, 2) }} {{ $transfer->source_currency }}</td>
            </tr>
            <tr>
                <td>سعر الصرف:</td>
                <td style="text-align: left;">{{ number_format((float)$transfer->exchange_rate, 4) }}</td>
            </tr>
            <tr>
                <td>العمولة المقتطعة:</td>
                <td style="text-align: left;">{{ number_format((float)$transfer->commission, 2) }} {{ $transfer->source_currency }}</td>
            </tr>
        </tbody>
    </table>

    <div class="amount-section">
        <div style="font-size: 11px; color: #4b5563;">المبلغ الصافي المستحق للاستلام:</div>
        <div class="amount-val">{{ number_format((float)$transfer->received_amount, 2) }} {{ $transfer->target_currency }}</div>
        <div class="amount-words">فقط {{ $amountInWords }} لا غير</div>
    </div>

    <div class="secret-code-section">
        <div class="secret-code-label">الرمز السري للاستلام (سري للغاية)</div>
        <div class="secret-code-val">{{ $transfer->secret_code }}</div>
    </div>

    <div class="qr-section">
        <img class="qr-image" src="{{ $qrCodeUrl }}" alt="QR Code">
        <div style="font-size: 9px; color: #6b7280; margin-top: 4px;">امسح الرمز للتحقق من صحة الإيصال</div>
    </div>

    <div class="footer">
        نشكركم على اختيار خدماتنا. يرجى الاحتفاظ بهذا الإيصال.<br>
        سعر الصرف متطابق مع أسعار الصرف الرسمية المعتمدة في الإدارة.
    </div>
</body>
</html>
