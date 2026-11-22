{{-- Partner API documentation (public, Arabic / Turkish). Code samples are always left-to-right. --}}
@php($t = fn (string $arText, string $trText) => $ar ? $arText : $trText)

<div class="min-h-screen bg-slate-50" dir="{{ $ar ? 'rtl' : 'ltr' }}" lang="{{ $lang }}">
    {{-- Header --}}
    <header class="bg-slate-900 text-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8 sm:py-10">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('logo.png?v=2') }}" alt="" class="w-11 h-11 rounded-xl bg-white/10 p-1">
                    <div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">API v1</div>
                        <h1 class="text-2xl sm:text-3xl font-black">{{ $t('واجهة API لباقات الإنترنت', 'İnternet Paketleri API') }}</h1>
                    </div>
                </div>
                <div class="inline-flex bg-white/10 rounded-xl p-1">
                    <button type="button" wire:click="switchLang('ar')" class="px-4 py-1.5 rounded-lg text-sm font-black transition {{ $ar ? 'bg-white text-slate-900' : 'text-slate-300 hover:text-white' }}">العربية</button>
                    <button type="button" wire:click="switchLang('tr')" class="px-4 py-1.5 rounded-lg text-sm font-black transition {{ !$ar ? 'bg-white text-slate-900' : 'text-slate-300 hover:text-white' }}">Türkçe</button>
                </div>
            </div>
            <p class="mt-4 text-slate-300 leading-relaxed max-w-3xl">
                {{ $t('اربط نظامك بنظامنا واطلب باقات الإنترنت لزبائنك تلقائياً: تعرف رصيدك، وتعرض الباقات، وترسل الطلب، وتتابع حالته. كل طلب يُخصم من رصيد حسابك عندنا بنفس الأسعار والقواعد المعتمدة في الموقع.', 'Sisteminizi bizimkine bağlayın ve müşterileriniz için internet paketlerini otomatik sipariş edin: bakiyenizi öğrenin, paketleri listeleyin, talep gönderin ve durumunu takip edin. Her talep, sitedeki fiyat ve kurallarla hesabınızdaki bakiyeden düşülür.') }}
            </p>
            <nav class="mt-6 flex flex-wrap gap-2 text-sm">
                @foreach([['start', $t('البداية السريعة', 'Hızlı başlangıç')], ['basics', $t('الأساسيات', 'Temel bilgiler')], ['endpoints', $t('الخدمات', 'Servisler')], ['statuses', $t('حالات الطلب', 'Talep durumları')], ['phones', $t('أرقام الهاتف', 'Telefon numaraları')], ['errors', $t('الأخطاء', 'Hatalar')], ['php', 'PHP']] as [$anchor, $label])
                    <a href="#{{ $anchor }}" class="px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 font-bold transition">{{ $label }}</a>
                @endforeach
            </nav>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8 space-y-8">
        {{-- Quick start --}}
        <section id="start" class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-7">
            <h2 class="text-xl font-black text-slate-900">{{ $t('البداية السريعة', 'Hızlı başlangıç') }}</h2>
            <ol class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach([
                    [$t('مفتاح API', 'API anahtarı'), $t('تُصدر الإدارة مفتاحاً خاصاً بحسابك. المفتاح سرّي ويُحفظ في نظامك فقط.', 'Yönetim, hesabınıza özel bir anahtar verir. Anahtar gizlidir ve yalnızca sisteminizde saklanır.'), 'vc_xxxxxxxxxxxx'],
                    [$t('إرسال المفتاح', 'Anahtarı gönderin'), $t('تُضاف هذه الترويسة إلى كل طلب:', 'Bu başlık her isteğe eklenir:'), 'Authorization: Bearer vc_…'],
                    [$t('أول تجربة', 'İlk deneme'), $t('طلب GET إلى العنوان التالي يعيد رصيدك، وبه يُتأكد أن الربط يعمل:', 'Aşağıdaki adrese GET isteği bakiyenizi döndürür; bağlantının çalıştığı böylece doğrulanır:'), 'GET /balance'],
                ] as $i => [$stepTitle, $stepText, $stepCode])
                    <li class="rounded-xl bg-slate-50 border border-slate-100 p-4">
                        <div class="w-8 h-8 rounded-full bg-primary-600 text-white font-black flex items-center justify-center">{{ $i + 1 }}</div>
                        <div class="mt-3 font-black text-slate-800">{{ $stepTitle }}</div>
                        <p class="mt-1 text-sm text-slate-600 leading-relaxed">{{ $stepText }}</p>
                        <code class="mt-2 block rounded-lg bg-slate-900 text-slate-100 px-3 py-2 text-xs font-mono break-all" dir="ltr">{{ $stepCode }}</code>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- Basics --}}
        <section id="basics" class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-7">
            <h2 class="text-xl font-black text-slate-900">{{ $t('الأساسيات', 'Temel bilgiler') }}</h2>
            <dl class="mt-4 divide-y divide-slate-100 text-sm">
                @foreach([
                    [$t('العنوان الأساسي', 'Temel adres'), $baseUrl, true],
                    [$t('التوثيق', 'Kimlik doğrulama'), 'Authorization: Bearer vc_YOUR_KEY', true],
                    [$t('صيغة البيانات', 'Veri biçimi'), 'JSON (Content-Type: application/json)', true],
                    [$t('العملة', 'Para birimi'), 'TRY', true],
                    [$t('حد الطلبات', 'İstek sınırı'), $t('60 طلباً في الدقيقة لكل مفتاح', 'Anahtar başına dakikada 60 istek'), false],
                    [$t('عنوان IP', 'IP adresi'), $t('يمكن حصر المفتاح بعناوين IP محددة عند الطلب من الإدارة', 'Anahtar, yönetimden istenerek belirli IP adresleriyle sınırlandırılabilir'), false],
                ] as [$label, $value, $isCode])
                    <div class="py-3 grid grid-cols-1 sm:grid-cols-3 gap-1">
                        <dt class="font-bold text-slate-500">{{ $label }}</dt>
                        <dd class="sm:col-span-2 {{ $isCode ? 'font-mono text-slate-900 break-all' : 'text-slate-800' }}" @if($isCode) dir="ltr" @endif>{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div>
                    <div class="font-bold text-emerald-700 mb-1">{{ $t('عند النجاح', 'Başarılı yanıt') }}</div>
                    <code class="block rounded-lg bg-slate-900 text-slate-100 px-3 py-2 text-xs font-mono" dir="ltr">{ "ok": true, ... }</code>
                </div>
                <div>
                    <div class="font-bold text-rose-700 mb-1">{{ $t('عند الخطأ', 'Hatalı yanıt') }}</div>
                    <code class="block rounded-lg bg-slate-900 text-slate-100 px-3 py-2 text-xs font-mono" dir="ltr">{ "ok": false, "error": { "code": "...", "message": "..." } }</code>
                </div>
            </div>
        </section>

        {{-- Endpoints --}}
        <section id="endpoints" class="space-y-6">
            <h2 class="text-xl font-black text-slate-900 px-1">{{ $t('الخدمات', 'Servisler') }}</h2>
            @foreach($endpoints as $endpoint)
                <article id="{{ $endpoint['id'] }}" class="bg-white rounded-2xl border border-slate-200 overflow-hidden" wire:key="endpoint-{{ $endpoint['id'] }}">
                    <div class="p-5 sm:p-7">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="font-mono text-xs font-black px-2.5 py-1 rounded-lg {{ $endpoint['method'] === 'POST' ? 'bg-amber-100 text-amber-900' : 'bg-emerald-100 text-emerald-900' }}">{{ $endpoint['method'] }}</span>
                            <code class="font-mono text-base font-black text-slate-900" dir="ltr">{{ $endpoint['path'] }}</code>
                            <span class="text-slate-400">·</span>
                            <h3 class="font-black text-slate-800">{{ $endpoint['title'] }}</h3>
                        </div>
                        <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $endpoint['desc'] }}</p>

                        @if($endpoint['params'])
                            <div class="mt-4 overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="text-xs text-slate-500 bg-slate-50">
                                        <tr>
                                            <th class="px-3 py-2 text-start">{{ $t('الحقل', 'Alan') }}</th>
                                            <th class="px-3 py-2 text-start">{{ $t('إلزامي', 'Zorunlu') }}</th>
                                            <th class="px-3 py-2 text-start">{{ $t('الوصف', 'Açıklama') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($endpoint['params'] as [$field, $required, $about])
                                            <tr>
                                                <td class="px-3 py-2 font-mono font-bold text-slate-900" dir="ltr">{{ $field }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap">{{ $required }}</td>
                                                <td class="px-3 py-2 text-slate-600">{{ $about }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 border-t border-slate-200">
                        @foreach([[$t('مثال الطلب', 'İstek örneği'), $endpoint['curl']], [$t('مثال الرد', 'Yanıt örneği'), $endpoint['response']]] as [$codeTitle, $code])
                            <div class="bg-slate-900 {{ $loop->first ? 'lg:border-e border-slate-700' : 'border-t lg:border-t-0 border-slate-700' }}" x-data="{ copied: false }">
                                <div class="flex items-center justify-between px-4 py-2 text-xs font-bold text-slate-400 border-b border-slate-800">
                                    <span>{{ $codeTitle }}</span>
                                    <button type="button" @click="navigator.clipboard.writeText($refs.code.innerText); copied = true; setTimeout(() => copied = false, 1500)" class="hover:text-white">
                                        <span x-show="!copied">{{ $t('نسخ', 'Kopyala') }}</span><span x-show="copied" x-cloak>✓</span>
                                    </button>
                                </div>
                                <pre class="p-4 text-xs leading-relaxed text-slate-100 overflow-x-auto" dir="ltr"><code x-ref="code">{{ $code }}</code></pre>
                            </div>
                        @endforeach
                    </div>
                </article>
            @endforeach
        </section>

        {{-- Statuses --}}
        <section id="statuses" class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-7">
            <h2 class="text-xl font-black text-slate-900">{{ $t('حالات الطلب', 'Talep durumları') }}</h2>
            <div class="mt-4 space-y-3">
                @foreach($statuses as [$code, $tone, $meaning])
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                        <span class="w-24 shrink-0 text-center font-mono text-sm font-black px-3 py-1 rounded-lg {{ $tone }}">{{ $code }}</span>
                        <span class="text-sm text-slate-700">{{ $meaning }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Phone numbers --}}
        <section id="phones" class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-7">
            <h2 class="text-xl font-black text-slate-900">{{ $t('أرقام الهاتف', 'Telefon numaraları') }}</h2>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="rounded-xl bg-slate-50 border border-slate-100 p-4">
                    <div class="font-black text-slate-800">{{ $t('باقات الشريحة الفيزيائية', 'Fiziksel SIM paketleri') }} <span class="font-mono text-xs text-slate-500">type = physical</span></div>
                    <p class="mt-1 text-slate-600">{{ $t('رقم الخط: 10 أرقام يبدأ بـ 50.', 'Hat numarası: 50 ile başlayan 10 hane.') }}</p>
                    <code class="mt-2 inline-block font-mono font-bold text-slate-900" dir="ltr">5051234567</code>
                </div>
                <div class="rounded-xl bg-slate-50 border border-slate-100 p-4">
                    <div class="font-black text-slate-800">{{ $t('باقات eSIM', 'eSIM paketleri') }} <span class="font-mono text-xs text-slate-500">type = esim</span></div>
                    <p class="mt-1 text-slate-600">{{ $t('رقم جوال تركي يصله رابط التثبيت: 10 أرقام يبدأ بـ 5.', 'Kurulum linkinin gideceği Türk cep numarası: 5 ile başlayan 10 hane.') }}</p>
                    <code class="mt-2 inline-block font-mono font-bold text-slate-900" dir="ltr">5551234567</code>
                </div>
            </div>
            <p class="mt-3 text-sm text-slate-600">{{ $t('تُقبل الصيغ 0555… و+90 555… والمسافات، وتُحوَّل تلقائياً إلى 10 أرقام.', '0555…, +90 555… biçimleri ve boşluklar kabul edilir; otomatik olarak 10 haneye çevrilir.') }}</p>
        </section>

        {{-- Errors --}}
        <section id="errors" class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-7">
            <h2 class="text-xl font-black text-slate-900">{{ $t('الأخطاء', 'Hatalar') }}</h2>
            <pre class="mt-4 rounded-xl bg-slate-900 p-4 text-xs text-slate-100 overflow-x-auto" dir="ltr"><code>{
  "ok": false,
  "error": { "code": "insufficient_balance", "message": "..." }
}</code></pre>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs text-slate-500 bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-start">error.code</th>
                            <th class="px-3 py-2 text-start">HTTP</th>
                            <th class="px-3 py-2 text-start">{{ $t('المعنى', 'Anlamı') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($errorCodes as [$code, $http, $meaning])
                            <tr>
                                <td class="px-3 py-2 font-mono font-bold text-slate-900" dir="ltr">{{ $code }}</td>
                                <td class="px-3 py-2 font-mono">{{ $http }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $meaning }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- PHP --}}
        <section id="php" class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="p-5 sm:p-7">
                <h2 class="text-xl font-black text-slate-900">{{ $t('مثال كامل بلغة PHP', 'Tam PHP örneği') }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ $t('طلب باقة ثم متابعة حالتها حتى تنتهي.', 'Paket talebi gönderip sonuçlanana kadar takip etme.') }}</p>
            </div>
            <div class="bg-slate-900" x-data="{ copied: false }">
                <div class="flex items-center justify-end px-4 py-2 text-xs font-bold text-slate-400 border-b border-slate-800">
                    <button type="button" @click="navigator.clipboard.writeText($refs.php.innerText); copied = true; setTimeout(() => copied = false, 1500)" class="hover:text-white">
                        <span x-show="!copied">{{ $t('نسخ', 'Kopyala') }}</span><span x-show="copied" x-cloak>✓</span>
                    </button>
                </div>
                <pre class="p-4 text-xs leading-relaxed text-slate-100 overflow-x-auto" dir="ltr"><code x-ref="php">{{ $phpExample }}</code></pre>
            </div>
        </section>

        {{-- Good practice --}}
        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 sm:p-7">
            <h2 class="text-lg font-black text-amber-900">{{ $t('ملاحظات مهمة', 'Önemli notlar') }}</h2>
            <ul class="mt-3 space-y-2 text-sm text-amber-900 list-disc ps-5">
                <li>{{ $t('reference فريد لكل طلب: عند انقطاع الاتصال يُعاد الطلب بنفس reference بأمان، فلا يُخصم مرتين.', 'reference her talep için benzersizdir: bağlantı koparsa talep aynı reference ile güvenle tekrar gönderilir, iki kez ücret alınmaz.') }}</li>
                <li>{{ $t('الطلب الفاشل (failed) يُعاد مبلغه إلى الرصيد تلقائياً.', 'Başarısız (failed) talebin tutarı bakiyeye otomatik iade edilir.') }}</li>
                <li>{{ $t('المفتاح سرّي: لا يوضع في تطبيقات الجوال أو صفحات المتصفح، بل في الخادم فقط. عند تسرّبه تُصدر الإدارة مفتاحاً جديداً ويتوقف القديم فوراً.', 'Anahtar gizlidir: mobil uygulamalara veya tarayıcı sayfalarına değil, yalnızca sunucuya konur. Sızarsa yönetim yeni anahtar verir ve eskisi hemen durur.') }}</li>
                <li>{{ $t('قائمة الباقات وأسعارها قد تتغيّر، والأفضل جلبها من /packages قبل عرضها للزبائن.', 'Paket listesi ve fiyatları değişebilir; müşterilere göstermeden önce /packages üzerinden almak en iyisidir.') }}</li>
            </ul>
        </section>
    </main>

    <footer class="max-w-5xl mx-auto px-4 sm:px-6 pb-10 text-center text-xs text-slate-400">
        © {{ date('Y') }} {{ config('app.name') }} · API v1
    </footer>
</div>
