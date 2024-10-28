<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $jsonFilePath = database_path('json/example_page.json');

        // Odczytaj zawartość pliku
        $jsonData = file_get_contents($jsonFilePath);

        // Wstaw dane do tabeli
        DB::table('cms_pages')->insert([
            'name' => 'Strona Główna - EXAMPLE DASHBOARD',
            'json_page' => $jsonData,
            'to_view' => '{"basic_website_structure_title":"Naprawa Automatyki Przemys\u0142owej Gliwice \u2013 Bart\u0142omiej Bernat,","basic_website_structure_description":"Profesjonalna naprawa automatyki przemys\u0142owej w Gliwicach. Bart\u0142omiej Bernat \u2013 serwis, diagnostyka i modernizacja system\u00f3w automatyki. Gwarancja jako\u015bci i precyzji.","basic_website_structure_favicon_img_file":"http:\/\/localhost\/oatllo\/public\/storage\/uploads\/empty_image.jpg","basic_website_structure_favicon_img_alt":"","basic_website_structure_keywords":"https:\/\/temp-dashboard.oatllo.pl\/","basic_website_structure_canonical":"https:\/\/temp-dashboard.oatllo.pl\/","basic_website_structure_op_title":"Tytu\u0142 do udost\u0119pnienia na Facebooku","basic_website_structure_op_description":"Opis strony do wy\u015bwietlenia w mediach spo\u0142eczno\u015bciowych","basic_website_structure_op_image_img_file":"http:\/\/localhost\/oatllo\/public\/storage\/uploads\/empty_image.jpg","basic_website_structure_op_image_img_alt":"","basic_website_structure_op_url":"https:\/\/example.com\/adres-strony","header_title":"Bart\u0142omiej Biernat","header_image_full_img_file":"http:\/\/localhost\/oatllo\/public\/assets\/images\/header.webp","header_image_full_img_alt":"","header_head_1":"Naprawa i serwis automatyki przemys\u0142owej","header_head_2":"Profesjonalna naprawa i serwis automatyki przemys\u0142owej. Szybkie usuwanie usterek, modernizacja system\u00f3w i wsparcie techniczne. Gwarancja jako\u015bci!","header_button_1_btn_href":"#","header_button_1_btn_text":"Sprawd\u017a szczeg\u00f3\u0142y","header_button_2_btn_href":"#","header_button_2_btn_text":"Czytaj wi\u0119cej","navbar_1_btn_href":"http:\/\/localhost\/oatllo\/public","navbar_1_btn_text":"Strona g\u0142\u00f3wna","navbar_2_btn_href":"http:\/\/localhost\/oatllo\/public\/blog","navbar_2_btn_text":"Blog","navbar_3_btn_href":"http:\/\/localhost\/oatllo\/public","navbar_3_btn_text":"Kontakt","three_columns_first_image_img_file":"http:\/\/localhost\/oatllo\/public\/assets\/images\/wozki-widlowe.jpg","three_columns_first_image_img_alt":"Two models wearing womens black cotton crewneck tee and off-white cotton crewneck tee.","three_columns_first_head":"Naprawa elektroniki w w\u00f3zkach wid\u0142owych","three_columns_first_text":"Specjalistyczna naprawa elektroniki w\u00f3zk\u00f3w wid\u0142owych","three_columns_first_url_link":"http:\/\/localhost\/oatllo\/public\/blog","three_columns_second_image_img_file":"http:\/\/localhost\/oatllo\/public\/assets\/images\/naprawa-falownikow-fotowoltaicznych.jpg","three_columns_second_image_img_alt":"Wooden shelf with gray and olive drab green baseball caps, next to wooden clothes hanger with sweaters.","three_columns_second_head":"Naprawa falownik\u00f3w fotowoltaicznych","three_columns_second_text":"Serwis i naprawa falownik\u00f3w do fotowoltaiki","three_columns_second_url_link":"http:\/\/localhost\/oatllo\/public\/blog","three_columns_third_image_img_file":"http:\/\/localhost\/oatllo\/public\/assets\/images\/naprawa-automatyki-przemyslowej.jpg","three_columns_third_image_img_alt":"Walnut desk organizer set with white modular trays, next to porcelain mug on wooden desk.","three_columns_third_head":"Naprawa automatyki przemys\u0142owej","three_columns_third_text":"Skuteczna naprawa system\u00f3w automatyki przemys\u0142owej","three_columns_third_url_link":"http:\/\/localhost\/oatllo\/public\/blog","element_testimonials_image_img_file":"http:\/\/localhost\/oatllo\/public\/assets\/images\/naprawa-podzespolow-elektronicznych.jpg","element_testimonials_image_img_alt":"","element_testimonials_heading":"Naprawa podzespo\u0142\u00f3w elektronicznych","element_testimonials_paragraph":"Profesjonalna naprawa podzespo\u0142\u00f3w elektronicznych \u2013 szybka diagnoza i serwis.","element_testimonials_link_btn_href":"{{route(\'article\')}}","element_testimonials_link_btn_text":"Zobacz wi\u0119cej","element_information_second_one_image_img_file":"http:\/\/localhost\/oatllo\/public\/assets\/images\/HMI-naprawa.png","element_information_second_one_image_img_alt":"","element_information_second_one_text1":"Naprawa paneli operatorskich HMI","element_information_second_one_text2":"Serwis i naprawa paneli HMI","element_information_second_one_button_btn_href":"{{route(\'article\')}}","element_information_second_one_button_btn_text":"Zobacz wi\u0119cej","element_information_second_two_image_img_file":"http:\/\/localhost\/oatllo\/public\/assets\/images\/prostownik.jpg","element_information_second_two_image_img_alt":"","element_information_second_two_text1":"Naprawa prostownika","element_information_second_two_text2":"Profesjonalna naprawa prostownik\u00f3w","element_information_second_two_button_btn_href":"{{route(\'article\')}}","element_information_second_two_button_btn_text":"Zobacz wi\u0119cej","element_article_heading":"Ostatnie artyku\u0142y","element_article_paragraph":"Ostatnie artyku\u0142y i nowinki z bran\u017cy automatyki","element26":"Kontakt","element27":"Masz pytania? Skontaktuj si\u0119 bezpo\u015brednio! Wype\u0142nij formularz lub zadzwo\u0144, oferuj\u0119 fachowe doradztwo i szybk\u0105 pomoc techniczn\u0105.","element28":"Jasna 25 <br\/>Gliwice","element29":"+48 504511170","element30":"kontakt@serwis-elektroniki-bartlomiej-biernat.pl","element32_btn_href":"#","element32_btn_text":"Wy\u015blij wiadomo\u015b\u0107","element33":"Serwis elektroniki - Bart\u0142omiej Biernat"}'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};
