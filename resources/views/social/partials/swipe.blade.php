{{--
    Podpowiedź "Swipe".

    Strzałka jest INLINE SVG, nie znakiem '→' – U+2192 nie mieści się w
    unicode-range subsetu latin naszego woff2, więc podmieniłby się na font
    systemowy w środku linii. SocialPostLinter pilnuje tego w treści, a tutaj
    problem znika u źródła. NIE zamieniaj tego na encję ani na tekst.
--}}
<div class="swipe">
    <span>Swipe</span>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"
         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M9 6l6 6-6 6"/>
    </svg>
</div>
