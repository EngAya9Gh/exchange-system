<?php

namespace App\Livewire\Public;

use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Public documentation of the partner API (/api-docs), Arabic and Turkish.
 * Meant to be sent to partners who connect their own system to ours.
 * The texts live here (not in the Blade file) because the PHP sample contains "<?php", which Blade would parse.
 */
class ApiDocs extends Component
{
    #[Url]
    public string $lang = '';

    public function mount(): void
    {
        if (!in_array($this->lang, ['ar', 'tr'], true)) {
            $this->lang = app()->getLocale() === 'ar' ? 'ar' : 'tr';
        }
    }

    public function switchLang(string $lang): void
    {
        $this->lang = in_array($lang, ['ar', 'tr'], true) ? $lang : 'ar';
    }

    public function render()
    {
        $baseUrl = rtrim(url('/api/v1'), '/');
        $ar = $this->lang === 'ar';
        $t = fn (string $arText, string $trText) => $ar ? $arText : $trText;

        $exampleOrder = <<<JSON
{
  "ok": true,
  "duplicate": false,
  "order": {
    "order_number": "SIM2611221234",
    "reference": "ORDER-1001",
    "status": "pending",
    "package_id": 3,
    "package_name_ar": "باقة تركيا 5 جيجا - 30 يوم (فيزيائية)",
    "package_name_tr": "Türkiye 5 GB - 30 Gün (Fiziksel)",
    "phone": "5051234567",
    "price": 270,
    "charged": 270,
    "failure_reason": null,
    "created_at": "2026-11-22T15:30:00+03:00",
    "updated_at": "2026-11-22T15:30:00+03:00"
  },
  "balance": {
    "balance": 730,
    "credit_limit": 0,
    "unlimited": false,
    "available": 730,
    "currency": "TRY"
  }
}
JSON;

        $endpoints = [
            [
                'id' => 'balance', 'method' => 'GET', 'path' => '/balance',
                'title' => $t('معرفة الرصيد', 'Bakiye sorgulama'),
                'desc' => $t('يعيد رصيد حسابك المتاح لشراء الباقات.', 'Paket almak için kullanılabilir hesap bakiyenizi döndürür.'),
                'curl' => "curl {$baseUrl}/balance \\\n  -H \"Authorization: Bearer vc_YOUR_KEY\"",
                'response' => "{\n  \"ok\": true,\n  \"balance\": {\n    \"balance\": 1000,\n    \"credit_limit\": 0,\n    \"unlimited\": false,\n    \"available\": 1000,\n    \"currency\": \"TRY\"\n  }\n}",
                'params' => [],
            ],
            [
                'id' => 'packages', 'method' => 'GET', 'path' => '/packages',
                'title' => $t('قائمة الباقات', 'Paket listesi'),
                'desc' => $t('الباقات المتاحة للبيع الآن مع أسعارها. رقم الباقة (id) هو ما يُرسل عند الطلب.', 'Şu an satışta olan paketler ve fiyatları. Talep gönderirken paket numarası (id) kullanılır.'),
                'curl' => "curl {$baseUrl}/packages \\\n  -H \"Authorization: Bearer vc_YOUR_KEY\"",
                'response' => "{\n  \"ok\": true,\n  \"packages\": [\n    {\n      \"id\": 3,\n      \"name_ar\": \"باقة تركيا 5 جيجا - 30 يوم (فيزيائية)\",\n      \"name_tr\": \"Türkiye 5 GB - 30 Gün (Fiziksel)\",\n      \"region\": \"turkey\",\n      \"type\": \"physical\",\n      \"network\": \"Tele CARD\",\n      \"data_gb\": 5,\n      \"validity_days\": 30,\n      \"price\": 270,\n      \"currency\": \"TRY\",\n      \"phone_format\": \"50XXXXXXXX\"\n    }\n  ]\n}",
                'params' => [],
            ],
            [
                'id' => 'create-order', 'method' => 'POST', 'path' => '/orders',
                'title' => $t('طلب باقة', 'Paket talebi'),
                'desc' => $t('يُخصم سعر الباقة من رصيدك وتُحمَّل على الرقم. الحالة الأولى غالباً pending، ثم تصبح success أو failed.', 'Paket fiyatı bakiyenizden düşülür ve numaraya yüklenir. İlk durum genelde pending olur, sonra success veya failed olur.'),
                'curl' => "curl -X POST {$baseUrl}/orders \\\n  -H \"Authorization: Bearer vc_YOUR_KEY\" \\\n  -H \"Content-Type: application/json\" \\\n  -d '{\"package_id\": 3, \"phone\": \"5051234567\", \"reference\": \"ORDER-1001\"}'",
                'response' => $exampleOrder,
                'params' => [
                    ['package_id', $t('نعم', 'Evet'), $t('رقم الباقة من قائمة الباقات.', 'Paket listesindeki paket numarası.')],
                    ['phone', $t('نعم', 'Evet'), $t('رقم الخط (انظر قواعد الأرقام أدناه).', 'Hat numarası (aşağıdaki numara kurallarına bakın).')],
                    ['reference', $t('نعم', 'Evet'), $t('رقم الطلب في نظامك، فريد لكل طلب (حروف إنجليزية وأرقام و - _ . حتى 64 حرفاً). إرساله مرة ثانية يعيد الطلب نفسه ولا يُخصم مرتين.', 'Sizin sisteminizdeki talep numarası, her talep için benzersiz (İngilizce harf, rakam ve - _ . en fazla 64 karakter). Tekrar gönderilirse aynı talep döner, iki kez ücret alınmaz.')],
                    ['customer_name', $t('لا', 'Hayır'), $t('اسم الزبون (اختياري).', 'Müşteri adı (isteğe bağlı).')],
                    ['notes', $t('لا', 'Hayır'), $t('ملاحظات (اختياري).', 'Notlar (isteğe bağlı).')],
                ],
            ],
            [
                'id' => 'order-status', 'method' => 'GET', 'path' => '/orders/{id}',
                'title' => $t('حالة الطلب', 'Talep durumu'),
                'desc' => $t('{id} هو رقم طلبنا (order_number) أو رقم الطلب في نظامك (reference).', '{id}, bizim talep numaramız (order_number) veya sizin talep numaranızdır (reference).'),
                'curl' => "curl {$baseUrl}/orders/ORDER-1001 \\\n  -H \"Authorization: Bearer vc_YOUR_KEY\"",
                'response' => "{\n  \"ok\": true,\n  \"order\": {\n    \"order_number\": \"SIM2611221234\",\n    \"reference\": \"ORDER-1001\",\n    \"status\": \"success\",\n    \"...\": \"...\"\n  }\n}",
                'params' => [],
            ],
        ];

        $statuses = [
            ['pending', 'bg-sky-100 text-sky-800', $t('جاري التحميل. يُعاد الاستعلام كل 30 إلى 60 ثانية حتى تتغيّر الحالة.', 'Yükleniyor. Durum değişene kadar 30-60 saniyede bir tekrar sorgulanır.')],
            ['success', 'bg-emerald-100 text-emerald-800', $t('تمّ تحميل الباقة على الرقم.', 'Paket numaraya yüklendi.')],
            ['failed', 'bg-rose-100 text-rose-800', $t('لم تُحمَّل الباقة وأُعيد المبلغ إلى رصيدك. السبب في failure_reason.', 'Paket yüklenmedi ve tutar bakiyenize iade edildi. Sebep failure_reason alanındadır.')],
        ];

        $errorCodes = [
            ['unauthorized', 401, $t('المفتاح غير موجود أو غير صحيح أو موقوف.', 'Anahtar eksik, hatalı veya durdurulmuş.')],
            ['ip_not_allowed', 403, $t('عنوان IP غير مسموح لهذا المفتاح.', 'Bu IP adresine bu anahtar için izin verilmiyor.')],
            ['validation_error', 422, $t('حقل ناقص أو غير صحيح (التفاصيل في fields).', 'Eksik veya hatalı alan (ayrıntılar fields içinde).')],
            ['package_unavailable', 422, $t('الباقة غير موجودة أو غير متاحة للبيع.', 'Paket yok veya satışta değil.')],
            ['invalid_phone', 422, $t('رقم الهاتف لا يناسب نوع الباقة.', 'Telefon numarası paket türüne uygun değil.')],
            ['insufficient_balance', 422, $t('الرصيد لا يكفي لهذه الباقة.', 'Bakiye bu paket için yeterli değil.')],
            ['order_not_found', 404, $t('لا يوجد طلب بهذا الرقم في حسابك.', 'Hesabınızda bu numarayla bir talep yok.')],
            ['too_many_requests', 429, $t('تجاوز الحد: 60 طلباً في الدقيقة.', 'Sınır aşıldı: dakikada 60 istek.')],
            ['server_error', 500, $t('خطأ غير متوقع، يُعاد المحاولة لاحقاً بنفس reference.', 'Beklenmeyen hata; aynı reference ile daha sonra tekrar denenebilir.')],
        ];

        $phpExample = <<<PHP
<?php
\$apiKey  = 'vc_YOUR_KEY';
\$baseUrl = '{$baseUrl}';

function callApi(string \$method, string \$url, string \$apiKey, ?array \$body = null): array
{
    \$ch = curl_init(\$url);
    curl_setopt_array(\$ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => \$method,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . \$apiKey, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => \$body ? json_encode(\$body) : null,
        CURLOPT_TIMEOUT        => 60,
    ]);
    \$response = json_decode(curl_exec(\$ch), true);
    curl_close(\$ch);

    return \$response ?? ['ok' => false];
}

// 1) Order a package (the reference is your own order id)
\$result = callApi('POST', "\$baseUrl/orders", \$apiKey, [
    'package_id' => 3,
    'phone'      => '5051234567',
    'reference'  => 'ORDER-1001',
]);

// 2) Follow it until it is "success" or "failed"
\$status = \$result['order']['status'] ?? null;
while (\$status === 'pending') {
    sleep(30);
    \$status = callApi('GET', "\$baseUrl/orders/ORDER-1001", \$apiKey)['order']['status'] ?? null;
}
PHP;

        return view('livewire.public.api-docs', compact('ar', 'baseUrl', 'endpoints', 'statuses', 'errorCodes', 'phpExample'))
            ->layout('layouts.public');
    }
}
