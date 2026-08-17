<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>إشعار سداد فاتورة - {{ $bill->tahsilat_api_islem_id }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');

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
            max-width: 850px;
            margin: 0 auto;
            background-color: #ffffff; 
            box-sizing: border-box;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 40px 50px;
            position: relative;
        }

        /* Header Layout */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #dc2626; /* Red line like e-Devlet */
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header-logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-logo img {
            max-width: 150px;
            max-height: 60px;
        }

        .header-right {
            text-align: left;
        }

        .warning-box {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #dc2626;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .warning-box svg {
            width: 16px;
            height: 16px;
        }

        /* Title Area */
        .page-title {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 5px;
        }

        .page-subtitle {
            font-size: 16px;
            color: #4b5563;
            margin-bottom: 30px;
            font-weight: 600;
        }

        /* Data Rows */
        .data-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px dotted #9ca3af; /* Dotted line */
        }

        .data-label {
            flex: 0 0 35%;
            font-weight: 700;
            color: #1f2937;
            font-size: 14px;
        }

        .data-value {
            flex: 1;
            color: #111827;
            font-size: 14px;
        }

        /* Actions Footer */
        .actions-container {
            max-width: 850px;
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
            border-radius: 8px;
            font-weight: 700;
            font-family: 'Cairo', sans-serif;
            font-size: 14px;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s;
        }
        
        .btn svg { width: 18px; height: 18px; margin-left: 8px; }

        .btn-blue { background-color: #2563eb; color: white; }
        .btn-blue:hover { background-color: #1d4ed8; }

        .btn-white { background-color: white; color: #374151; border-color: #d1d5db; }
        .btn-white:hover { background-color: #f9fafb; }

        /* Toast Notification */
        #toast {
            visibility: hidden;
            min-width: 250px;
            background-color: #10b981;
            color: #fff;
            text-align: center;
            border-radius: 8px;
            padding: 16px;
            position: fixed;
            z-index: 1000;
            left: 50%;
            bottom: 30px;
            font-size: 14px;
            font-weight: bold;
            transform: translateX(-50%) translateY(20px);
            opacity: 0;
            transition: opacity 0.3s, transform 0.3s;
        }

        #toast.show {
            visibility: visible;
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        @media print {
            body { padding: 0; background-color: white; }
            .receipt-container { border: none; max-width: 100%; box-shadow: none; padding: 0; }
            .actions-container, #toast { display: none !important; }
        }

    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</head>
<body>
    <div class="receipt-container">
        <!-- Header -->
        <div class="header">
            <div class="header-logo">
                <!-- You can replace logo.png with the official company logo -->
                <img src="{{ asset('logo.png?v=2') }}" alt="شعار النظام" onerror="this.style.display='none'">
                <div style="font-size: 24px; font-weight: 700; color: #dc2626; margin-right: 10px;">
                    نظام التسديدات
                </div>
            </div>
            <div class="header-right">
                <div class="warning-box">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    هذه الوثيقة للإثبات فقط
                </div>
            </div>
        </div>

        <!-- Titles -->
        <div class="page-title">إشعار سداد فاتورة إلكتروني</div>
        <div class="page-subtitle">نظام الاستعلام والتسديد المباشر</div>

        <!-- Data Rows -->
        <div class="data-row">
            <div class="data-label">الوكيل / نقطة البيع</div>
            <div class="data-value">{{ $bill->user->name ?? 'غير معروف' }}</div>
        </div>

        <div class="data-row">
            <div class="data-label">رقم المشترك (Abone No)</div>
            <div class="data-value">{{ $bill->abone_no }}</div>
        </div>

        <div class="data-row">
            <div class="data-label">رقم المرجع للإيصال</div>
            <div class="data-value" style="font-family: monospace; font-size: 15px;">{{ $bill->tahsilat_api_islem_id }}</div>
        </div>

        @if($bill->fatura_no)
        <div class="data-row">
            <div class="data-label">رقم الفاتورة (Fatura No)</div>
            <div class="data-value">{{ $bill->fatura_no }}</div>
        </div>
        @endif

        <div class="data-row">
            <div class="data-label">تاريخ ووقت السداد</div>
            <div class="data-value" dir="ltr" style="text-align: right;">{{ $bill->created_at->format('Y-m-d H:i:s') }}</div>
        </div>

        <div class="data-row">
            <div class="data-label">حالة العملية</div>
            <div class="data-value" style="font-weight: 700; color: {{ $bill->api_status === 'completed' ? '#059669' : ($bill->api_status === 'pending' ? '#d97706' : '#dc2626') }}">
                @if($bill->api_status === 'completed')
                    مكتملة (Ödendi)
                @elseif($bill->api_status === 'pending')
                    قيد المعالجة (Bekliyor)
                @else
                    فاشلة / مستردة (İptal/İade) - {{ $bill->api_status_message }}
                @endif
            </div>
        </div>

        <div class="data-row">
            <div class="data-label">مبلغ الفاتورة الأساسي</div>
            <div class="data-value">{{ number_format((float)$bill->amount, 2) }} ل.ت</div>
        </div>

        <div class="data-row">
            <div class="data-label">عمولة التحصيل</div>
            <div class="data-value">{{ number_format((float)$bill->commission, 2) }} ل.ت</div>
        </div>

        <div class="data-row" style="background-color: #f9fafb; padding: 15px 10px; margin-top: 10px; border-bottom: 2px solid #e5e7eb;">
            <div class="data-label" style="font-size: 16px; color: #111827;">الإجمالي المدفوع (TL)</div>
            <div class="data-value" style="font-size: 18px; font-weight: 700; color: #dc2626;">
                {{ number_format((float)$bill->total_deducted, 2) }}
            </div>
        </div>
        
        <div class="data-row" style="border: none;">
            <div class="data-label">المبلغ كتابةً</div>
            <div class="data-value" style="font-weight: 600;">{{ $amountInWords }}</div>
        </div>
        
        <!-- Footer info -->
        <div style="margin-top: 60px; font-size: 11px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 15px; text-align: center;">
            تم إصدار هذا الإيصال إلكترونياً من خلال نظام التسديد المباشر. يُرجى الاحتفاظ برقم المرجع للاستعلام في حال دعت الحاجة لذلك.
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
            مشاركة الصورة
        </button>
        <button class="btn btn-white" onclick="window.close()" style="margin-right: auto;">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            إغلاق
        </button>
    </div>

    <!-- Toast Element -->
    <div id="toast">
        تمت العملية بنجاح!
    </div>

    <script>
        async function shareReceipt() {
            var btn = event.currentTarget;
            var originalText = btn.innerHTML;
            btn.innerHTML = '<svg class="animate-spin ml-2" style="width:18px;height:18px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> جاري...';
            btn.disabled = true;

            try {
                const element = document.querySelector('.receipt-container');
                
                const canvas = await html2canvas(element, {
                    scale: 2,
                    backgroundColor: '#ffffff'
                });
                
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
