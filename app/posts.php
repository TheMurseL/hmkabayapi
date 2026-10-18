<?php

declare(strict_types=1);

function post_defaults(): array
{
    return [
        'kaba-insaatta-dogru-is-programi' => [
            'title' => 'Kalıp işinde çalışma sırası',
            'excerpt' => 'Kalıp işi başlamadan önce proje, metraj ve demir işlerinin sırası netleştirilir.',
            'content' => 'Kalıp yapılacak bölümleri projeden inceleyerek işe başlıyoruz. Kalıp hazırlığı, demir donatı ve beton uygulamalarının sırasını birlikte planlıyoruz. Tuğla veya çatı işi isteniyorsa bunları da iş programına ekliyoruz.',
            'intent' => 'ahşap kalıp iş programı',
            'service' => 'istanbul-kaba-insaat',
            'published' => 0,
        ],
        'betonarme-uygulamalarda-dikkat-ettigimiz-5-konu' => [
            'title' => 'Ahşap kalıp işçiliğinde nelere bakıyoruz?',
            'excerpt' => 'Kalıp ölçüleri, tahta hazırlığı ve uygulamaların çalışma sırası.',
            'content' => 'Ahşap kalıbı çakmadan önce uygulama projesindeki ölçüleri inceliyoruz. Tahta ve kereste hazırlığını yapılacak işe göre planlıyoruz. Kalıp hazırlığını demir donatı ve beton uygulama programına göre planlıyoruz.',
            'intent' => 'ahşap kalıp işçiliği',
            'service' => 'istanbul-kaba-insaat',
            'published' => 0,
        ],
        'kaba-insaat-teklifi-karsilastirma' => [
            'title' => 'Kalıp işçiliği teklifinde nelere bakılır?',
            'excerpt' => 'Teklifleri aynı kalıp metrajı ve aynı işçilik kapsamıyla karşılaştırın.',
            'content' => <<<'TEXT'
## Yapılacak işi netleştirin

Teklif isterken aynı projeyi ve aynı kalıp metrajını paylaşın. Ahşap kalıp işçiliğini, demir işlerini ve isteniyorsa tuğla ile çatı işlerini ayrı kalemler olarak belirtin.

## Malzeme ve işçilik kapsamı

Tahta, kereste ve diğer kalıp malzemelerini kimin sağlayacağını baştan konuşun. Fiyatları karşılaştırırken metrajın ölçüm yöntemini, işçilik bedelini ve başlangıç tarihini birlikte değerlendirin.

[Kaba inşaat hizmetimizi](/istanbul-kaba-insaat) inceleyebilir, proje ve konum bilgilerinizi [iletişim formundan](/iletisim) paylaşabilirsiniz.
TEXT,
            'intent' => 'kalıp işçiliği teklifi karşılaştırma',
            'service' => 'istanbul-kaba-insaat',
            'published' => 1,
        ],
        'kaba-insaat-maliyet-hesabi-kapsam' => [
            'title' => 'Kalıp işçiliğinde metraj ve maliyet hesabı',
            'excerpt' => 'Kalıp yüzey alanını ve işçilik birim fiyatını aynı kapsam üzerinden hesaplayın.',
            'content' => <<<'TEXT'
## Hangi alan hesaplanır?

Kalıp metrajı, kalıp yapılacak yüzeylerin alanıdır. Arsa alanı veya binanın kat alanı ile aynı değildir. Temel, kolon, kiriş, döşeme ve merdiven kalıplarının metrajı projeden belirlenir.

## İşçilik bedeli

Ön hesapta kalıp alanı, anlaşılan m² işçilik bedeliyle çarpılır. Demir, tuğla ve çatı işleri isteniyorsa kendi metraj ve fiyatlarıyla ayrıca değerlendirilir.

[Kalıp ve demir işçiliği hesaplayıcısında](/hesaplamalar/kaba-insaat-maliyeti) kalıp alanını, demir miktarını ve kendi birim fiyatlarınızı kullanın. Projeye göre teklif almak için [bize ulaşın](/iletisim).
TEXT,
            'intent' => 'kalıp işçiliği metraj ve maliyet hesabı',
            'service' => 'istanbul-kaba-insaat',
            'published' => 1,
        ],
        'villa-kaba-insaat-oncesi-hazirlik' => [
            'title' => 'Villa kaba inşaatı için teklif isterken',
            'excerpt' => 'Proje, konum ve uygulama kapsamını paylaşarak villa kaba inşaat teklifine hazırlanın.',
            'content' => <<<'TEXT'
## İlk görüşme için gerekenler

- Projenin konumu ve mevcut çizimleri.
- Kat sayısı ve kalıp, demir donatı ile beton uygulanacak bölümler.
- Hedef başlangıç tarihi.
- Kalıp malzemesi, demir ve betonu kimin sağlayacağı.

Villa projelerinde kalıp, demir donatı ve beton uygulamaları yapıyoruz. Tuğla ve çatı uygulamaları da isteniyorsa görüşmede belirtin; teklifi bu kapsama göre hazırlayalım.

[Villa kaba inşaat](/villa-kaba-insaat) sayfasını inceleyebilir, çizim ve konum bilgilerinizi [bize iletebilirsiniz](/iletisim).
TEXT,
            'intent' => 'villa kaba inşaat teklifi',
            'service' => 'villa-kaba-insaat',
            'published' => 1,
        ],
    ];
}
