import { useCallback, useEffect, useState } from "react";
import {
  Easing,
  continueRender,
  delayRender,
  interpolate,
  staticFile,
} from "remotion";

/**
 * Tokeny designu Oatllo dla scen clipa + ładowanie fontu.
 *
 * To DRUGA (lekka) powierzchnia designu obok skórek social (Blade). Świadomie:
 * sceny clipa to natywne komponenty React (kinetic type, nie layouty slajdów),
 * więc nie da się ich renderować z tego samego Blade co PNG. Trzymamy zgodność
 * wizualną (neutral-950 + akcent + Montserrat), a nie współdzielony kod.
 *
 * Font Montserrat to zmienny woff2 w public/fonts — ładowany przez staticFile,
 * więc render jest OFFLINE (bez sieci), jak base64 po stronie PNG.
 */

export const COLORS = {
  bg: "#0a0a0a", // neutral-950
  ink: "#fafafa", // neutral-50
  muted: "#a3a3a3", // neutral-400
  faint: "#525252", // neutral-600
  panel: "#171717", // neutral-900 (tło bloków kodu)
  rule: "#262626", // neutral-800
};

export const FONT = "Montserrat, system-ui, sans-serif";
export const MONO = "ui-monospace, 'Cascadia Code', 'Fira Code', monospace";

/**
 * Styl bazowy dla KAŻDEGO tekstu monospace (kod, terminal, compare).
 *
 * Ligatury MUSZĄ być wyłączone: fonty do kodu (Cascadia/Fira) renderują `->`
 * jako '→', a to (a) fałszuje kod i (b) jest DOKŁADNIE glifem, którego zakazuje
 * lint (spoza subsetu fontu napisów). Bez tego `$user->posts` wychodzi
 * `$user→posts`. `calt`/`liga` off gasi to niezależnie od fontu zastępczego.
 */
export const MONO_STYLE: React.CSSProperties = {
  fontFamily: MONO,
  fontVariantLigatures: "none",
  fontFeatureSettings: '"liga" 0, "calt" 0',
};

/**
 * Rozmiar fontu kodu tak dobrany, żeby NAJDŁUŻSZA linia zmieściła się w panelu.
 * Monospace ma stały advance (~0.6em), więc font = szerokość / (kolumny * 0.6),
 * przycięty do czytelnego zakresu. Dzięki temu krótki kod jest duży, a długi się
 * nie urywa (twardy limit 46 kolumn i tak pilnuje lint).
 */
export const codeFontSize = (
  longestLine: number,
  availableWidth: number,
): number => {
  const fit = availableWidth / (Math.max(1, longestLine) * 0.6);

  return Math.max(28, Math.min(48, Math.floor(fit)));
};

/** Krzywa wjazdu — jedna dla wszystkiego, żeby ruch był spójny (jak w reelu). */
export const EASE = Easing.bezier(0.16, 1, 0.3, 1);

export const reveal = (frame: number, start: number, duration = 16): number =>
  interpolate(frame, [start, start + duration], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
    easing: EASE,
  });

/** Styl "wjazd od dołu + fade" — bazowy ruch treści. */
export const rise = (
  frame: number,
  start: number,
  distance = 28,
): React.CSSProperties => {
  const r = reveal(frame, start);

  return {
    opacity: r,
    transform: `translateY(${(1 - r) * distance}px)`,
  };
};

/** Poświata akcentu — miękka, żeby scena nie była płaska. */
export const glow = (accent: string): React.CSSProperties => ({
  position: "absolute",
  inset: 0,
  background: `radial-gradient(60% 40% at 50% 32%, ${accent}22, transparent 70%)`,
  pointerEvents: "none",
});

/**
 * @font-face dla zmiennego Montserrata z public/fonts. Jeden plik pokrywa całą
 * oś wag (100–900), więc deklarujemy zakres, a nie plik na wagę.
 */
export const fontFaceCss = (): string => `
  @font-face {
    font-family: 'Montserrat';
    font-style: normal;
    font-weight: 100 900;
    font-display: block;
    src: url(${staticFile("fonts/montserrat.woff2")}) format('woff2');
  }
`;

/**
 * Czeka na font przed pierwszą klatką. Bez tego początek wyszedłby fontem
 * zastępczym o innych metrykach — ta sama mina, przed którą chroni
 * EmbeddedFontProvider po stronie PNG i Slide.tsx po stronie reela.
 */
export const useFonts = (): void => {
  const [handle] = useState(() => delayRender("Ładowanie fontu clipa"));
  const onReady = useCallback(() => continueRender(handle), [handle]);

  useEffect(() => {
    document.fonts.ready.then(onReady);
  }, [onReady]);
};
