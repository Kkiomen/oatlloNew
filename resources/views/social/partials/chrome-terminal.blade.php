{{--
    Pasek tytułowy okna terminala dla skórki `terminal`.

    Dołączany przez layout, gdy styl ma `chrome => 'terminal'` w
    config/social-styles.php. To jedyny styl w pakiecie, który potrzebuje własnego
    markupu – reszta to czysty CSS. Style tego paska mieszkają w skórce
    (social/styles/terminal.blade.php), nie tutaj.
--}}
<div class="term-bar">
    <span class="term-light term-light-1"></span>
    <span class="term-light term-light-2"></span>
    <span class="term-light term-light-3"></span>
    <span class="term-title">~/oatllo &mdash; zsh</span>
</div>
