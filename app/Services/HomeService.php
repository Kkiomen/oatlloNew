<?php

declare(strict_types=1);

namespace App\Services;

class HomeService
{

    public static function getRouteCourses(): string
    {
        $defaultLangue = env('APP_LOCALE');
        if($defaultLangue == 'pl'){
            return route('courses');
        }

        return route('courses_en');
    }

    public static function getTagManagerHEAD(): string
    {
        $defaultLangue = env('APP_LOCALE');
        if($defaultLangue == 'pl'){
            return "
            <!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-N9MC7J53');</script>
<!-- End Google Tag Manager -->
<!-- Google tag (gtag.js) -->
<script async src=\"https://www.googletagmanager.com/gtag/js?id=G-D4D0GSS6WQ\"></script>
<script>
            window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-D4D0GSS6WQ');
</script>
";
        }
        return "<!-- Google tag (gtag.js) -->
<script async src=\"https://www.googletagmanager.com/gtag/js?id=G-T7YR3P4C1Q\"></script>
<script>
    window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-T7YR3P4C1Q');
</script>";
    }

    public static function getTagManagerBODY(): string
    {
        $defaultLangue = env('APP_LOCALE');
        if($defaultLangue == 'pl'){
            return '
                <!-- Google Tag Manager (noscript) -->
                <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-N9MC7J53"
                                  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
                <!-- End Google Tag Manager (noscript) -->
            ';
        }
        return '';
    }
}
