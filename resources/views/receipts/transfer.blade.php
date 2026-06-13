<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>إيصال حوالة - {{ $transfer->transfer_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            direction: rtl;
            text-align: right;
            padding: 0;
            margin: 0;
            background-color: #f8f9fa;
        }
        .receipt-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            background-color: #fffaf5; /* Warm slight pinkish/orange tint from the image */
            position: relative;
            min-height: 500px;
            box-sizing: border-box;
            border: 1px solid #eee;
        }

        /* Top Header Grid */
        .top-section {
            padding: 30px 40px 10px;
            display: table;
            width: 100%;
            table-layout: fixed;
            box-sizing: border-box;
        }

        .top-col {
            display: table-cell;
            text-align: center;
            vertical-align: top;
        }

        /* Adjust individual columns */
        .col-source { width: 15%; text-align: right; }
        .col-dest { width: 25%; }
        .col-date { width: 25%; }
        .col-number { width: 20%; text-align: left;}
        .col-logo { width: 15%; text-align: left; }

        .header-label {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .header-value {
            font-size: 14px;
            font-weight: bold;
            color: #000;
        }

        .text-blue { color: #0000ff; }

        .logo-circle {
            width: 80px;
            height: 80px;
            background-color: #000;
            border-radius: 50%;
            display: inline-block;
            border: 3px solid #d4af37; /* Gold border */
            position: relative;
        }
        .logo-text {
            color: #d4af37;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            line-height: 80px;
        }

        /* Middle Gray Section */
        .middle-gray-section {
            background-color: #e5e5e5;
            padding: 20px 40px;
            margin-top: 10px;
            border-top: 1px solid #ccc;
            border-bottom: 1px solid #ccc;
        }

        .gray-table {
            width: 100%;
            table-layout: fixed;
        }

        .gray-col {
            text-align: center;
        }

        .gray-col-right { text-align: right; }
        .gray-col-left { text-align: left; }

        .text-red {
            color: #ff0000;
        }

        .amount-section {
            text-align: center;
            margin-top: 20px;
        }

        .amount-number {
            font-size: 18px;
            font-weight: bold;
            color: #ff0000;
            margin-bottom: 5px;
        }

        .amount-words {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }

        /* Bottom Section */
        .bottom-section {
            padding: 20px 40px;
        }

        .delivery-address {
            margin-bottom: 20px;
            text-align: right;
        }

        .divider {
            border-top: 2px dashed #333;
            margin: 20px 0;
        }

        .important-notes {
            text-align: center;
        }
        
        .important-notes .notes-title {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .important-notes .note-item {
            font-size: 13px;
            margin-bottom: 5px;
            font-weight: bold;
        }

    </style>
</head>
<body>
    <div class="receipt-container">
        
        <div class="top-section">
            <div class="top-col col-source">
                <div class="header-label">المصدر</div>
                <div class="header-value">{{ $transfer->user_id ?? '543' }}</div>
            </div>
            
            <div class="top-col col-dest">
                <div class="header-label">الوجهه</div>
                <div class="header-value">{{ $transfer->destination ?? '149 - جميع المحافظات' }}</div>
            </div>
            
            <div class="top-col col-date">
                <div class="header-label">التاريخ</div>
                <div class="header-value">{{ $transfer->created_at->format('H:i:s Y-m-d') }}</div>
            </div>
            
            <div class="top-col col-number">
                <div class="header-label">رقم الاشعار</div>
                <div class="header-value text-blue">{{ $transfer->transfer_number }}</div>
            </div>
            
            <div class="top-col col-logo">
                <div class="logo-circle">
                    <div class="logo-text">شعار بردى</div>
                </div>
            </div>
        </div>

        <div class="middle-gray-section">
            <table class="gray-table">
                <tr>
                    <td class="gray-col gray-col-right">
                        <div class="header-label">المستفيد</div>
                        <div class="header-value">{{ $transfer->recipient_name ?: '0' }}</div>
                    </td>
                    <td class="gray-col">
                        <div class="header-label">الجوال</div>
                        <div class="header-value">{{ $transfer->recipient_phone }}</div>
                    </td>
                    <td class="gray-col gray-col-left">
                        <div class="header-label">الرقم السري</div>
                        <div class="header-value text-red">{{ $transfer->secret_code }}</div>
                    </td>
                </tr>
            </table>

            <div class="amount-section">
                <div class="amount-number">{{ number_format((float)$transfer->received_amount, 0) }} {{ $transfer->target_currency == 'EGP' ? 'جنيه' : $transfer->target_currency }}</div>
                <div class="amount-words">{{ $amountInWords }}</div>
            </div>
        </div>

        <div class="bottom-section">
            <div class="delivery-address">
                <div class="header-label">عنوان التسليم</div>
                <div class="header-value">{{ $transfer->destination }} - {{ $transfer->address ?: 'عنوان' }} - {{ $transfer->recipient_phone }}</div>
            </div>

            <div class="divider"></div>

            <div class="important-notes">
                <div class="notes-title">ملاحظات هامة</div>
                <div class="note-item">- يتم تسليم الحوالة بيد المستلم حصراً بعد التأكد من الهوية الأصلية ولا تقبل الصورة.</div>
                <div class="note-item">- لا تشارك هذا الإيصال الا مع المستلم حرصا على سلامة أموالك.</div>
            </div>
        </div>

    </div>
</body>
</html>
