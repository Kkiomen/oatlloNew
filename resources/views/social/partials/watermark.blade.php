{{--
    Logo technologii jako znak wodny. Logo pochodzi z config/course-covers.php
    (kanwa 0 0 100 100, currentColor) – te same znaki co okładki kursów.

    Motyw 'php' ma CELOWO puste logo, dlatego layout dołącza ten partial przez
    @includeWhen(trim($logo) !== ''). Bez tego guardu dostalibyśmy pusty <svg>.
--}}
<div class="watermark">
    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        {!! $logo !!}
    </svg>
</div>
