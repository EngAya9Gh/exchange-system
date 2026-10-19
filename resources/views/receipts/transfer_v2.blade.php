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
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            position: relative;
            min-height: 500px;
            box-sizing: border-box;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            padding: 20px 30px;
        }

        /* Top Logo */
        .logo-container {
            text-align: left; /* Left in RTL means right side of screen? Wait, RTL text-align: left means it goes to the left side. RTL text-align: right means right side. In the image, logo is on top left in RTL? No, Arabic is RTL, so logo on the left of the page is actually end of the line. Wait, looking at the image, "ALFA CASH" is on the top LEFT or RIGHT? The text "رقم الاشعار" is on the right. The logo "ALFA CASH" is on the TOP LEFT! Because "ALFA CASH" is English and positioned on the left. Let me re-check the image. Yes, the image has logo on the top LEFT! */
            margin-bottom: 20px;
        }
        .logo {
            display: inline-block;
            text-align: center;
            color: #1a5632; /* Dark green text */
        }
        .logo-circle {
            width: 30px;
            height: 30px;
            background-color: #8b1e28; /* Dark red */
            border-radius: 50%;
            display: inline-block;
            position: relative;
            margin-bottom: 5px;
        }
        .logo-circle::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 10px;
            height: 10px;
            background-color: white;
            border-radius: 50%;
            border-top-right-radius: 0;
        }
        .logo-text {
            font-weight: 900;
            font-size: 20px;
            letter-spacing: 1px;
            line-height: 1;
        }
        .logo-sub {
            font-size: 10px;
            letter-spacing: 2px;
            color: #666;
            margin-top: 2px;
            border-top: 1px solid #ccc;
            padding-top: 2px;
        }

        /* Info Table */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: #fcfcfc;
        }
        .info-table td {
            border: 1px solid #eee;
            padding: 12px 15px;
            font-size: 14px;
        }
        .info-label {
            font-weight: bold;
            color: #555;
            width: 30%;
        }
        .info-value {
            font-weight: bold;
            color: #222;
        }
        .text-green { color: #15803d !important; }
        .text-red { color: #dc2626 !important; }

        .flex-row {
            display: flex;
            width: 100%;
        }
        .flex-col {
            flex: 1;
            display: flex;
            border: 1px solid #eee;
        }
        .flex-col:not(:first-child) {
            border-right: none;
        }
        .flex-cell {
            padding: 12px 15px;
            display: flex;
            align-items: center;
        }
        .cell-label {
            font-weight: bold;
            color: #888;
            margin-left: 10px;
            white-space: nowrap;
        }
        .cell-value {
            font-weight: bold;
            color: #222;
            flex: 1;
            text-align: right;
        }
        .row-border-bottom {
            border-bottom: none;
        }

        /* Amount Box */
        .amount-box {
            border: 2px dashed #8b1e28;
            border-radius: 12px;
            text-align: center;
            padding: 20px;
            margin-bottom: 20px;
            background-color: #fff;
        }
        .amount-label {
            font-weight: bold;
            color: #8b1e28;
            font-size: 16px;
            margin-bottom: 10px;
        }
        .amount-value {
            color: #dc2626;
            font-size: 28px;
            font-weight: 900;
            margin-bottom: 5px;
        }
        .amount-words {
            color: #444;
            font-size: 15px;
            font-weight: bold;
        }

        /* Delivery & Source Box */
        .delivery-box {
            background-color: #f7e6e8; /* Light pinkish */
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
            border: 1px solid #f0d5d8;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.05;
            font-size: 150px;
            color: #8b1e28;
            z-index: 0;
            pointer-events: none;
        }

        .delivery-content {
            position: relative;
            z-index: 1;
        }

        .delivery-title {
            color: #8b1e28;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .delivery-text {
            color: #333;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .delivery-phone {
            color: #555;
            font-size: 13px;
            margin-bottom: 15px;
            direction: ltr;
            text-align: right;
        }

        .source-date-row {
            display: flex;
            justify-content: space-between;
            border-top: 1px dashed #d1b3b6;
            padding-top: 15px;
        }
        .sd-item {
            display: flex;
            align-items: center;
        }
        .sd-label {
            color: #8b1e28;
            font-weight: bold;
            font-size: 14px;
            margin-left: 10px;
        }
        .sd-value {
            color: #222;
            font-weight: bold;
            font-size: 14px;
        }

        /* Notes */
        .notes-box {
            border-right: 4px solid #ed6c57;
            padding: 5px 20px 5px 0;
            margin-bottom: 30px;
        }
        .notes-title {
            color: #ed6c57;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .notes-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .notes-list li {
            position: relative;
            padding-right: 15px;
            font-size: 13px;
            color: #333;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .notes-list li::before {
            content: '•';
            position: absolute;
            right: 0;
            color: #ed6c57;
            font-weight: bold;
        }

        /* QR Codes */
        .qr-section {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .qr-box {
            text-align: center;
        }
        .qr-label {
            color: #8b1e28;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .qr-img {
            width: 100px;
            height: 100px;
            border: 1px solid #ddd;
            padding: 5px;
            border-radius: 8px;
            background-color: #fff;
        }
        /* WhatsApp Support Bar */
        .whatsapp-support-bar {
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: 10px 16px;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        }
        .ws-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .ws-icon-circle {
            width: 32px;
            height: 32px;
            background-color: #22c55e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 2px 4px rgba(34, 197, 94, 0.25);
        }
        .ws-icon-circle svg {
            width: 18px;
            height: 18px;
            fill: #ffffff;
        }
        .ws-text-group {
            display: flex;
            flex-direction: column;
        }
        .ws-title {
            font-size: 13px;
            font-weight: 800;
            color: #14532d;
            line-height: 1.3;
        }
        .ws-subtitle {
            font-size: 11px;
            color: #15803d;
            font-weight: 600;
        }
        .ws-phone-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: #ffffff;
            border: 1.5px solid #86efac;
            color: #15803d;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            direction: ltr;
            font-family: 'Cairo', monospace, sans-serif;
            letter-spacing: 0.5px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }
        .ws-phone-btn:hover {
            background-color: #22c55e;
            color: #ffffff;
            border-color: #22c55e;
        }

        /* Colored Shapes at bottom */
        .bottom-shapes {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 35px;
            z-index: 0;
            pointer-events: none;
        }
        .shape-black {
            position: absolute;
            bottom: 0;
            right: -20px;
            width: 55%;
            height: 45px;
            background-color: #1a202c;
            border-top-left-radius: 100px;
            transform: rotate(-4deg);
        }
        .shape-red {
            position: absolute;
            bottom: -10px;
            left: -20px;
            width: 45%;
            height: 40px;
            background-color: #8b1e28;
            border-top-right-radius: 100px;
            transform: rotate(4deg);
        }
        
        .content-wrapper {
            position: relative;
            z-index: 1;
            padding-bottom: 45px;
        }

        /* Actions Container */
        .actions-container {
            max-width: 600px;
            margin: 20px auto 0;
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            font-family: 'Cairo', sans-serif;
            font-size: 14px;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s;
        }
        
        .btn svg { width: 18px; height: 18px; margin-left: 8px; }

        .btn-blue { background-color: #3b82f6; color: white; }
        .btn-blue:hover { background-color: #2563eb; }

        .btn-red { background-color: #ef4444; color: white; }
        .btn-red:hover { background-color: #dc2626; }

        .btn-white { background-color: white; color: #374151; border-color: #d1d5db; }
        .btn-white:hover { background-color: #f9fafb; }
        
        .btn-switch { background-color: #10b981; color: white; }
        .btn-switch:hover { background-color: #059669; }

        #toast {
            visibility: hidden;
            min-width: 250px;
            background-color: #10b981;
            color: #fff;
            text-align: center;
            border-radius: 8px;
            padding: 12px;
            position: fixed;
            z-index: 1000;
            left: 50%;
            bottom: 30px;
            font-size: 14px;
            font-family: 'Cairo', sans-serif;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        #toast.show {
            visibility: visible;
            opacity: 1;
        }

        @media print {
            body { padding: 0; background-color: white; }
            .receipt-container { border: none; max-width: 100%; box-shadow: none; border-radius: 0; padding: 0; }
            .actions-container, #toast { display: none !important; }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</head>
<body>
    <div class="receipt-container">
        @if($transfer->status === 'rejected' || $transfer->status === 'cancelled')
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-size: 100px; color: rgba(255, 0, 0, 0.15); font-weight: 900; z-index: 0; pointer-events: none; border: 10px solid rgba(255, 0, 0, 0.15); padding: 20px; border-radius: 20px; text-align: center; line-height: 1.2;">
                مرفوضة<br><span style="font-size: 40px;">REJECTED</span>
            </div>
            <div style="background-color: #ef4444; color: white; text-align: center; padding: 10px; font-weight: bold; font-size: 16px; position: relative; z-index: 10; border-radius: 8px 8px 0 0; margin: -40px -40px 20px -40px;">
                ⚠️ هذه الحوالة مرفوضة وملغاة وغير صالحة
                @if($transfer->admin_notes)
                    <br><span style="font-size: 13px; font-weight: normal;">السبب: {{ $transfer->admin_notes }}</span>
                @endif
            </div>
        @endif
        <div class="content-wrapper" style="position: relative; z-index: 10;">
            <!-- Header Top Bar (Logo & WhatsApp Hotline) -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <a href="https://wa.me/905392065497" target="_blank" style="text-decoration: none; display: inline-flex; align-items: center; gap: 7px; background-color: #f0fdf4; border: 1px solid #bbf7d0; padding: 5px 12px; border-radius: 20px; color: #166534; font-size: 12px; font-weight: 700;">
                    <svg style="width: 15px; height: 15px; fill: #22c55e;" viewBox="0 0 24 24">
                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                    </svg>
                    <span style="color: #4b5563; font-weight: 600;">خدمة العملاء:</span>
                    <span dir="ltr" style="font-family: monospace, sans-serif; letter-spacing: 0.3px;">00905392065497</span>
                </a>
                <div class="logo-container" style="margin-bottom: 0;">
                    <img src="{{ asset('logo.png?v=2') }}" alt="Logo" style="max-height: 48px; object-fit: contain;">
                </div>
            </div>

            <!-- Header Info -->
            <div style="border: 1px solid #eee; background: #fafafa; margin-bottom: 20px;">
                <div class="flex-row" style="border-bottom: 1px solid #eee;">
                    <div class="flex-col">
                        <div class="flex-cell">
                            <span class="cell-label">رقم الاشعار:</span>
                            <span class="cell-value">{{ $transfer->transfer_number }}</span>
                        </div>
                    </div>
                    <div class="flex-col">
                        <div class="flex-cell">
                            <span class="cell-label">الرقم السري:</span>
                            <span class="cell-value text-green">{{ $transfer->secret_code }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex-row">
                    <div class="flex-col">
                        <div class="flex-cell">
                            <span class="cell-label">المستفيد:</span>
                            <span class="cell-value">{{ $transfer->recipient_name ?: '0' }}</span>
                        </div>
                    </div>
                    <div class="flex-col">
                        <div class="flex-cell">
                            <span class="cell-label">الجوال:</span>
                            <span class="cell-value">{{ $transfer->recipient_phone }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Amount -->
            <div class="amount-box">
                <div class="amount-label">المبلغ</div>
                <div class="amount-value">{{ number_format((float)$transfer->received_amount, 0) }} {{ $transfer->target_currency == 'EGP' ? 'جنيه' : ($transfer->target_currency == 'TRY' ? 'ليره تركيه' : $transfer->target_currency) }}</div>
                <div class="amount-words">{{ $amountInWords }}</div>
            </div>

            <!-- Delivery & Source -->
            <div class="delivery-box">
                <!-- A decorative watermark shape could go here -->
                <div class="watermark">❯</div>
                
                <div class="delivery-content">
                    <div class="delivery-title">عنوان التسليم:</div>
                    <div class="delivery-text">{{ $transfer->destination }} - {{ $transfer->address ?: 'عنوان التسليم غير محدد' }}</div>
                    <div class="delivery-phone">{{ $transfer->recipient_phone }}</div>
                    
                    <div class="source-date-row">
                        <div class="sd-item">
                            <span class="sd-label">المصدر:</span>
                            <span class="sd-value">{{ $transfer->user_id ?? '---' }}</span>
                        </div>
                        <div class="sd-item">
                            <span class="sd-label">التاريخ:</span>
                            <span class="sd-value">{{ $transfer->created_at->format('Y-m-d H:i:s') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="notes-box">
                <div class="notes-title">ملاحظات هامة:</div>
                <ul class="notes-list">
                    <li>يتم تسليم الحوالة بيد المستلم حصراً بعد التأكد من الهوية الأصلية ولا تقبل الصورة.</li>
                    <li>لا تشارك هذا الايصال الا مع المستلم حرصاً على سلامة أموالك.</li>
                </ul>
            </div>

            <!-- Dedicated WhatsApp Customer Support Bar -->
            <div class="whatsapp-support-bar">
                <div class="ws-info">
                    <div class="ws-icon-circle">
                        <svg viewBox="0 0 24 24">
                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                        </svg>
                    </div>
                    <div class="ws-text-group">
                        <span class="ws-title">خدمة العملاء والاستفسار (واتساب)</span>
                        <span class="ws-subtitle">تواصل معنا مباشرة عبر تطبيق واتساب</span>
                    </div>
                </div>
                <a href="https://wa.me/905392065497" target="_blank" class="ws-phone-btn">
                    <span>00905392065497</span>
                </a>
            </div>

            <!-- QR Codes -->
            <div class="qr-section">
                <div class="qr-box">
                    <div class="qr-label" style="color: #16a34a;">للتواصل واتساب</div>
                    <a href="https://wa.me/905392065497" target="_blank" style="text-decoration: none; display: inline-block;">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode('https://wa.me/905392065497') }}" class="qr-img" alt="WhatsApp QR">
                    </a>
                </div>
                <div class="qr-box">
                    <div class="qr-label" style="color: #ef4444;">أسعار الصرف</div>
                    <a href="{{ url('/ar/rates') }}" target="_blank" style="text-decoration: none; display: inline-block;">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode(url('/ar/rates')) }}" class="qr-img" alt="Exchange Rates">
                    </a>
                </div>
                <div class="qr-box">
                    <div class="qr-label">امسح للتسليم</div>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode(route('admin.transfers.deliver', $transfer->transfer_number)) }}" class="qr-img" alt="Scan to deliver">
                </div>
            </div>
        </div>

        <!-- Decorative Bottom Shapes -->
        <div class="bottom-shapes">
            <div class="shape-black"></div>
            <div class="shape-red"></div>
        </div>
    </div>

    <!-- Actions Footer -->
    <div class="actions-container">
        <button class="btn btn-blue" onclick="window.print()">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            طباعة
        </button>
        <button class="btn btn-blue" onclick="shareReceipt()" style="background-color: #2563eb;">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg>
            مشاركة
        </button>
        <button class="btn btn-white" onclick="copyTransferNumber()">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
            نسخ الرقم
        </button>
    </div>

    <!-- Toast Element -->
    <div id="toast">تم نسخ رقم الحوالة بنجاح!</div>

    <script>
        function copyTransferNumber() {
            navigator.clipboard.writeText('{{ $transfer->transfer_number }}').then(function() {
                var toast = document.getElementById("toast");
                toast.innerHTML = '<svg class="w-5 h-5 ml-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> تم نسخ الرقم السري بنجاح!';
                toast.className = "show";
                setTimeout(function(){ toast.className = toast.className.replace("show", ""); }, 3000);
            });
        }

        async function shareReceipt() {
            var btn = event.currentTarget;
            var originalText = btn.innerHTML;
            btn.innerHTML = '<svg class="w-5 h-5 ml-2 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> جاري التجهيز...';
            btn.disabled = true;

            try {
                const element = document.querySelector('.receipt-container');
                
                const canvas = await html2canvas(element, {
                    scale: 2, // High resolution
                    useCORS: true, // For the QR codes
                    backgroundColor: '#ffffff'
                });
                
                canvas.toBlob(async function(blob) {
                    const file = new File([blob], 'receipt-{{ $transfer->transfer_number }}.png', { type: 'image/png' });
                    
                    if (navigator.canShare && navigator.canShare({ files: [file] })) {
                        try {
                            await navigator.share({
                                files: [file]
                            });
                        } catch (error) {
                            console.error('Error sharing', error);
                        }
                    } else {
                        // Fallback for desktop browsers
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.style.display = 'none';
                        a.href = url;
                        a.download = 'receipt-{{ $transfer->transfer_number }}.png';
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        
                        var toast = document.getElementById("toast");
                        toast.innerHTML = '<svg class="w-5 h-5 ml-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> تم تحميل صورة الإشعار بنجاح للمشاركة!';
                        toast.className = "show";
                        setTimeout(function(){ toast.className = toast.className.replace("show", ""); }, 3000);
                    }
                    
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }, 'image/png');
                
            } catch (err) {
                console.error("Error capturing receipt:", err);
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
