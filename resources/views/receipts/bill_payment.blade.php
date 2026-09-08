<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>إشعار سداد فاتورة - {{ $bill->tahsilat_api_islem_id }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Courier+Prime:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Courier Prime', 'Courier New', Courier, monospace;
            direction: rtl;
            text-align: right;
            background-color: #e5e7eb;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .pos-receipt {
            background-color: #fff;
            width: 320px;
            padding: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            position: relative;
            color: #000;
        }

        /* Jagged edges for thermal paper look */
        .pos-receipt::before, .pos-receipt::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            height: 10px;
            background-size: 20px 20px;
            background-repeat: repeat-x;
        }
        .pos-receipt::before {
            top: -10px;
            background-image: radial-gradient(circle at 10px 0, transparent 11px, #fff 12px);
        }
        .pos-receipt::after {
            bottom: -10px;
            background-image: radial-gradient(circle at 10px 10px, transparent 11px, #fff 12px);
        }

        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .text-lg { font-size: 1.2rem; }
        .text-xl { font-size: 1.4rem; }
        .mb-2 { margin-bottom: 8px; }
        .mt-2 { margin-top: 8px; }
        .my-4 { margin-top: 16px; margin-bottom: 16px; }

        .divider {
            border-bottom: 1px dashed #000;
            margin: 10px 0;
        }

        .flex-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 4px;
            font-size: 13px;
        }

        .flex-row .label {
            flex: 1;
        }
        .flex-row .value {
            text-align: left;
            font-weight: bold;
        }

        .logo-img {
            max-width: 100px;
            margin-bottom: 10px;
        }

        /* Actions Footer */
        .actions-container {
            width: 360px;
            margin-top: 30px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 15px;
            border-radius: 6px;
            font-weight: bold;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 13px;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s;
        }
        
        .btn svg { width: 16px; height: 16px; margin-left: 6px; }

        .btn-blue { background-color: #2563eb; color: white; }
        .btn-blue:hover { background-color: #1d4ed8; }

        .btn-white { background-color: white; color: #374151; border-color: #d1d5db; }
        .btn-white:hover { background-color: #f9fafb; }

        @media print {
            body { 
                background-color: transparent; 
                padding: 0;
                align-items: flex-start;
            }
            .pos-receipt { box-shadow: none; width: 100%; max-width: 320px; }
            .pos-receipt::before, .pos-receipt::after { display: none; }
            .actions-container, #toast { display: none !important; }
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
</head>
<body>

    <div class="pos-receipt" id="receipt">
        <div class="text-center">
            <img src="{{ asset('logo.png?v=2') }}" alt="Logo" class="logo-img" onerror="this.style.display='none'">
            <div class="font-bold text-lg mb-2">نظام التسديدات</div>
            <div style="font-size: 12px;">إشعار سداد فاتورة إلكتروني</div>
            <div style="font-size: 12px;">{{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}</div>
        </div>

        <div class="divider"></div>

        <div class="text-center font-bold mb-2">
            TERMINAL ID: T-{{ rand(1000, 9999) }}<br>
            MERCHANT ID: M-{{ rand(100000, 999999) }}
        </div>

        <div class="divider"></div>

        <div class="flex-row">
            <span class="label">تاريخ السداد:</span>
            <span class="value" dir="ltr">{{ $bill->created_at->format('Y-m-d H:i') }}</span>
        </div>
        <div class="flex-row">
            <span class="label">رقم المرجع:</span>
            <span class="value">{{ $bill->tahsilat_api_islem_id }}</span>
        </div>
        <div class="flex-row">
            <span class="label">الوكيل / النقطة:</span>
            <span class="value">{{ $bill->user->name ?? 'غير معروف' }}</span>
        </div>

        <div class="divider"></div>

        <div class="flex-row">
            <span class="label">رقم المشترك:</span>
            <span class="value">{{ $bill->abone_no }}</span>
        </div>
        @if($bill->fatura_no)
        <div class="flex-row">
            <span class="label">رقم الفاتورة:</span>
            <span class="value">{{ $bill->fatura_no }}</span>
        </div>
        @endif
        
        <div class="divider"></div>

        <div class="flex-row">
            <span class="label">حالة العملية:</span>
            <span class="value">
                @if($bill->api_status === 'completed')
                    مكتملة (ÖDENDİ)
                @elseif($bill->api_status === 'pending')
                    قيد المعالجة (BEKLİYOR)
                @else
                    فاشلة (İPTAL)
                @endif
            </span>
        </div>

        <div class="flex-row">
            <span class="label">مبلغ الفاتورة:</span>
            <span class="value">{{ number_format((float)$bill->amount, 2) }} TL</span>
        </div>
        <div class="flex-row">
            <span class="label">العمولة:</span>
            <span class="value">{{ number_format((float)$bill->commission, 2) }} TL</span>
        </div>

        <div class="divider" style="border-bottom: 2px dashed #000;"></div>

        <div class="flex-row my-4">
            <span class="label font-bold text-lg">الإجمالي:</span>
            <span class="value font-bold text-xl">{{ number_format((float)$bill->total_deducted, 2) }} TL</span>
        </div>

        <div class="text-center" style="font-size: 11px;">
            {{ $amountInWords }}
        </div>

        <div class="divider" style="border-bottom: 2px dashed #000;"></div>

        <div class="text-center my-4">
            @if($bill->api_status === 'completed')
                <div class="font-bold text-xl">مقبولة</div>
                <div class="font-bold text-xl">APPROVED</div>
            @elseif($bill->api_status === 'pending')
                <div class="font-bold text-xl">قيد الانتظار</div>
                <div class="font-bold text-xl">PENDING</div>
            @else
                <div class="font-bold text-xl">مرفوضة</div>
                <div class="font-bold text-xl">DECLINED</div>
            @endif
        </div>

        <div class="text-center mb-2" style="font-size: 11px;">
            الرجاء الاحتفاظ بهذا الإيصال للرجوع إليه<br>
            RETAIN THIS COPY FOR YOUR RECORDS
        </div>
        
        <div class="text-center mb-2" style="font-size: 12px; font-weight: bold;">
            *** شكراً لكم ***
        </div>

        <div class="text-center mt-4">
            <svg id="barcode"></svg>
        </div>
    </div>

    <!-- Actions Footer -->
    <div class="actions-container">
        <button class="btn btn-blue" onclick="window.print()">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            طباعة
        </button>
        <button class="btn btn-white" onclick="shareReceipt()">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg>
            مشاركة
        </button>
        <button class="btn btn-white" onclick="window.close()" style="margin-right: auto;">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            إغلاق
        </button>
    </div>

    <!-- Toast Element -->
    <div id="toast" style="visibility: hidden; min-width: 250px; background-color: #10b981; color: #fff; text-align: center; border-radius: 8px; padding: 16px; position: fixed; z-index: 1000; left: 50%; bottom: 30px; font-size: 14px; font-weight: bold; transform: translateX(-50%) translateY(20px); opacity: 0; transition: opacity 0.3s, transform 0.3s;">
        تمت العملية بنجاح!
    </div>

    <script>
        // Generate Barcode
        JsBarcode("#barcode", "{{ $bill->tahsilat_api_islem_id }}", {
            format: "CODE128",
            lineColor: "#000",
            width: 1.5,
            height: 40,
            displayValue: false
        });

        async function shareReceipt() {
            var btn = event.currentTarget;
            var originalText = btn.innerHTML;
            btn.innerHTML = '<svg class="animate-spin ml-2" style="width:18px;height:18px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> جاري...';
            btn.disabled = true;

            try {
                const element = document.getElementById('receipt');
                const style = document.createElement('style');
                style.innerHTML = '.pos-receipt::before, .pos-receipt::after { display: none !important; }';
                document.head.appendChild(style);

                const canvas = await html2canvas(element, {
                    scale: 3,
                    backgroundColor: '#ffffff'
                });
                
                document.head.removeChild(style);

                canvas.toBlob(async function(blob) {
                    const file = new File([blob], 'bill-receipt.png', { type: 'image/png' });
                    
                    if (navigator.canShare && navigator.canShare({ files: [file] })) {
                        try {
                            await navigator.share({ files: [file] });
                        } catch (error) { console.error('Error sharing', error); }
                    } else {
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.style.display = 'none';
                        a.href = url;
                        a.download = 'bill-receipt.png';
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        
                        var toast = document.getElementById("toast");
                        toast.innerHTML = 'تم تحميل صورة الفاتورة بنجاح!';
                        toast.style.visibility = "visible";
                        toast.style.opacity = "1";
                        toast.style.transform = "translateX(-50%) translateY(0)";
                        setTimeout(function(){ 
                            toast.style.opacity = "0";
                            toast.style.transform = "translateX(-50%) translateY(20px)";
                            setTimeout(() => { toast.style.visibility = "hidden"; }, 300);
                        }, 3000);
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
