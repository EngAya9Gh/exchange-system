<?php

// «📘 Paketi tedarikçilere bağlama rehberi» in «Paket ve Fiyat Yönetimi» (partials/package-link-guide)
return [
    'title' => 'Paketi tedarikçilere bağlama rehberi',
    'show' => 'Rehberi göster',
    'hide' => 'Gizle',
    'intro' => 'Her tedarikçinin üç alanı vardır: Operatör (operator), Tip (tip) ve Kod (API). Üçü de doluysa paket o tedarikçiden otomatik yüklenir; boş bırakılırsa paket o tedarikçiye gönderilmez.',

    'where_title' => '1. Paket ayarlarının yeri',
    'where_1' => 'Yönetim paneli ← «Paket ve Fiyat Yönetimi» ← paketteki «Düzenle» düğmesi ya da yeni paket için «Paket Ekle».',
    'where_2' => 'Formun üstü: hat tipi (fiziksel veya eSIM), GB, süre ve fiyat. HIZLI paketlerde kaydetmeden önce «⚡ Hızlı paket (HIZLI)» işaretlenir.',
    'where_3' => 'Formun altındaki «Otomatik Yükleme» bölümünde her tedarikçi için bir liste vardır. Listeden paket seçilince üç alan kendiliğinden dolar; maliyet ve kâr görünür.',

    'panel_title' => '2. Listede olmayan paket: değerler tedarikçi panelinden',
    'panel_1' => 'Tedarikçi paneli (bayi.kontor86.com veya bayi.telekomtl.com, ikisinde de aynı sayfa) ← Kontor ← ⚙️ «Kontor Fiyat Ayarları» ← «Callback» düğmesi.',
    'panel_2' => 'TARİFE sütununda örneğin «Callback (TeleCard) HIZLI FİZİKSEL 50GB» yazar: ilk kelime operatördür (Callback), parantez içindeki kelime tiptir (TeleCard).',
    'panel_3' => 'API sütunu koddur; ALIŞ sütunu paketin bize maliyetidir.',
    'panel_5' => 'telekomtl\'de: TURKTELEKOM ürünlerinin tipi eSIM yazar ve Tele CARD fiziksel hatlarına yüklenenler bunlardır; tip paneldeki gibi eSIM yazılır.',
    'panel_4' => 'Yükleme sayfasındaki kutucuklarda görünen rakam (250₺ veya 2000₺ gibi) bir gösterim fiyatıdır, kod değildir.',

    'examples_title' => '3. Tedarikçi listelerinden örnekler',
    'col_product' => 'Tedarikçideki paket',
    'col_supplier' => 'Tedarikçi',
    'col_operator' => 'Operatör',
    'col_tip' => 'Tip',
    'col_code' => 'Kod',
    'col_cost' => 'Maliyet',
    'col_for' => 'Bizdeki paket',
    'for_physical' => 'Fiziksel (Tele CARD hattı)',
    'for_fast' => '⚡ Hızlı',
    'for_esim' => 'Türkiye eSIM',
    'for_europe' => 'Avrupa',

    'mistakes_title' => '4. Sık yapılan hatalar',
    'mistake_1' => '«Tedarikçideki Paket Adı» alanı sadece not içindir ve tedarikçiye gönderilmez; kod buraya yazılmaz.',
    'mistake_2' => 'Üç alan birlikte doldurulur ya da birlikte boş bırakılır.',
    'mistake_3' => 'Harfler paneldeki gibi yazılır: TeleCard, eSIM, OnlineKart.',
    'mistake_4' => '«⚡ Hızlı» paketler yalnızca kontor86\'ya bağlıdır; telekomtl HIZLI paket satmadığı için telekomtl alanları boş kalır.',
    'mistake_5' => 'Alanları boş yeni bir paket kaydedilince sistem onu aynı tip, GB ve süredeki ürüne bağlar; bu yüzden «⚡ Hızlı paket» kaydetmeden önce işaretlenir.',
    'mistake_6' => '«Satış fiyatı, tedarikçi maliyetinden … düşük» uyarısı, paketin fiyatından pahalı bir ürüne bağlı olduğunu gösterir.',
    'preferred' => '«Bu paket için tercih edilen tedarikçi» boşsa: «Tedarikçi Bağlantıları» ekranındaki ana tedarikçi kullanılır.',
];
