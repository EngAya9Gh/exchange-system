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
        @font-face {
            font-family: 'Cairo';
            font-style: normal;
            font-weight: 700;
            src: url('https://fonts.gstatic.com/s/cairo/v20/SLXW1O5wj4oSE1CjeQn8.ttf') format('truetype');
        }
        body {
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            text-align: right;
            padding: 40px 20px;
            margin: 0;
            background-color: #f3f4f6;
        }
        .receipt-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            background-color: #fffaf5; /* Warm slight pinkish/orange tint from the image */
            position: relative;
            min-height: 500px;
            box-sizing: border-box;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            overflow: hidden;
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

        .actions-container {
            max-width: 800px;
            margin: 20px auto 0;
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 900;
            font-family: 'Cairo', sans-serif;
            font-size: 15px;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .btn:active {
            transform: translateY(1px);
        }
        
        .btn svg { width: 18px; height: 18px; margin-left: 8px; }

        .btn-blue { background-color: #3b82f6; color: white; }
        .btn-blue:hover { background-color: #2563eb; }

        .btn-red { background-color: #ef4444; color: white; }
        .btn-red:hover { background-color: #dc2626; }

        .btn-white { background-color: white; color: #374151; border-color: #d1d5db; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); }
        .btn-white:hover { background-color: #f9fafb; border-color: #9ca3af; }

        /* Toast Notification */
        #toast {
            visibility: hidden;
            min-width: 250px;
            background-color: #10b981;
            color: #fff;
            text-align: center;
            border-radius: 50px;
            padding: 16px;
            position: fixed;
            z-index: 1000;
            left: 50%;
            bottom: 30px;
            font-size: 16px;
            font-weight: bold;
            font-family: 'Cairo', sans-serif;
            transform: translateX(-50%) translateY(20px);
            opacity: 0;
            transition: opacity 0.3s, transform 0.3s;
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        #toast.show {
            visibility: visible;
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        @media print {
            body { padding: 0; background-color: white; }
            .receipt-container { border: none; max-width: 100%; box-shadow: none; border-radius: 0; overflow: visible; }
            .actions-container, #toast { display: none !important; }
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
                <img src="{{ asset('logo.png?v=2') }}" alt="Logo" style="max-width: 80px; max-height: 80px; display: inline-block; object-fit: contain;">
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

        <div class="bottom-section" style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="flex: 1;">
                <div class="delivery-address" style="text-align: right;">
                    <div class="header-label">عنوان التسليم</div>
                    <div class="header-value">{{ $transfer->destination }} - {{ $transfer->address ?: 'عنوان' }} - {{ $transfer->recipient_phone }}</div>
                </div>

                <div class="important-notes" style="text-align: right; margin-top: 20px;">
                    <div class="notes-title" style="color: #ef4444;">ملاحظات هامة</div>
                    <div class="note-item">- يتم تسليم الحوالة بيد المستلم حصراً بعد التأكد من الهوية الأصلية ولا تقبل الصورة.</div>
                    <div class="note-item">- لا تشارك هذا الإيصال الا مع المستلم حرصا على سلامة أموالك.</div>
                </div>
            </div>
            
            <div class="qr-box" style="text-align: center; margin-right: 20px; border: 2px dashed #ccc; padding: 10px; border-radius: 12px; background: #fff;">
                <div class="header-label" style="margin-bottom: 5px; color: #ef4444;">امسح للتسليم</div>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode(route('admin.transfers.deliver', $transfer->transfer_number)) }}" alt="Scan to deliver" style="width: 90px; height: 90px;">
            </div>
        </div>
    </div>

    <!-- Actions Footer -->
    <div class="actions-container">
        <button class="btn btn-blue" onclick="window.print()">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            طباعة الإيصال
        </button>
        <button class="btn btn-red" onclick="window.print()">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            حفظ كـ PDF
        </button>
        <button class="btn btn-white" onclick="copyTransferNumber()">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
            نسخ رقم الحوالة
        </button>
        <a href="?theme=2" class="btn" style="background-color: #10b981; color: white; text-decoration: none;">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            التصميم الجديد
        </a>
        <button class="btn btn-white" onclick="window.close()" style="margin-right: auto; color: #6b7280;">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            إغلاق الصفحة
        </button>
    </div>

    <!-- Toast Element -->
    <div id="toast">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
        تم نسخ الرقم السري بنجاح!
    </div>

    <script>
        function copyTransferNumber() {
            navigator.clipboard.writeText('{{ $transfer->transfer_number }}').then(function() {
                var toast = document.getElementById("toast");
                toast.className = "show";
                setTimeout(function(){ toast.className = toast.className.replace("show", ""); }, 3000);
            });
        }
    </script>
</body>
</html>
