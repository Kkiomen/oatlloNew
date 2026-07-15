import { useCallback, useEffect, useState } from "react";
import {
  AbsoluteFill,
  Easing,
  continueRender,
  delayRender,
  interpolate,
  useCurrentFrame,
} from "remotion";
import type { ReelSlide } from "./types";

/**
 * Jeden slajd Reela.
 *
 * DOM slajdu NIE jest tu odtwarzany w Reakcie – wstrzykujemy dokładnie ten sam
 * HTML, który rasteryzator robi na PNG (`social:export --html-only`). Wygląd ma
 * jedno źródło (Blade) i wideo nie może się od kafelka rozjechać. Remotion dokłada
 * wyłącznie RUCH: nadpisuje `opacity`/`translate` znanych klas (`.headline`,
 * `.body > *`, `.footer`) blokiem CSS przeliczanym co klatkę.
 *
 * Skórka jest wklejona w `<style>` wewnątrz wstrzykniętego HTML-a, więc jej reguły
 * są GLOBALNE dla dokumentu Remotiona. To jest OK i zamierzone – jeden Reel to
 * jeden post, czyli jedna skórka, a slajdy w crossfadzie niosą IDENTYCZNY CSS.
 * Dzięki tej globalności tło 9:16 maluje sama skórka (patrz `Backdrop` w Reel.tsx).
 */

/** Krzywa wjazdu – ta sama dla wszystkiego, żeby ruch był spójny. */
const EASE = Easing.bezier(0.16, 1, 0.3, 1);

const reveal = (frame: number, start: number, duration = 16) =>
  interpolate(frame, [start, start + duration], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
    easing: EASE,
  });

/** `opacity` + delikatny wjazd od dołu. */
const rise = (frame: number, start: number, distance = 20) => {
  const r = reveal(frame, start);

  return `opacity: ${r}; translate: 0 ${(1 - r) * distance}px;`;
};

export const Slide: React.FC<{
  /** Numer slajdu – scopuje CSS. Patrz komentarz przy `scope` niżej. */
  index: number;
  slide: ReelSlide;
  html: string;
  canvas: { width: number; height: number };
}> = ({ index, slide, html, canvas }) => {
  const frame = useCurrentFrame();

  // Reguły MUSZĄ być scopowane numerem slajdu. Wstrzyknięty <style> jest globalny,
  // a `useCurrentFrame()` liczy się względem <Sequence>, więc przy dwóch slajdach
  // na ekranie naraz (crossfade) selektor `.reel-slide` trafiałby w OBA – wychodzący
  // dostawałby klatki wchodzącego i animował się od nowa w trakcie znikania.
  const scope = `.reel-slide-${index}`;

  // Fonty są wklejone w HTML jako base64 @font-face. Bez tego czekania pierwsze
  // klatki wyszłyby fontem zastępczym o innych metrykach – dokładnie ta sama mina,
  // przed którą chroni EmbeddedFontProvider po stronie PNG.
  const [handle] = useState(() => delayRender("Ładowanie fontów slajdu"));

  const onFontsReady = useCallback(() => continueRender(handle), [handle]);

  useEffect(() => {
    document.fonts.ready.then(onFontsReady);
  }, [onFontsReady]);

  // Stagger treści: każde kolejne dziecko `.body` wjeżdża 8 klatek po poprzednim.
  const bodyRules = Array.from({ length: slide.bodyChildren }, (_, i) => {
    return `${scope} .body > *:nth-child(${i + 1}) { ${rise(frame, 22 + i * 8)} }`;
  }).join("\n");

  // Blok kodu odsłania się wycieraczką z góry – czyta się jak dopisywanie linii,
  // a w odróżnieniu od prawdziwego typewritera nie rusza węzłów tekstowych
  // (te są we wstrzykniętym HTML-u i ich cięcie byłoby kruche).
  const codeWipe = interpolate(frame, [24, 52], [0, 100], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
    easing: EASE,
  });

  return (
    <AbsoluteFill style={{ justifyContent: "center", alignItems: "center" }}>
      <style>{`
        ${scope} .top      { ${rise(frame, 0, 12)} }
        ${scope} .headline { ${rise(frame, 6, 24)} }
        ${scope} .footer   { ${rise(frame, 30, 12)} }

        /* Podkreślenie rozjeżdża się od lewej – to jedyny element, który rośnie,
           a nie wjeżdża. */
        ${scope} .underline {
          transform-origin: left center;
          scale: ${reveal(frame, 14)} 1;
        }

        ${bodyRules}

        ${scope} .body pre {
          clip-path: inset(0 0 ${100 - codeWipe}% 0);
        }

        /* "Swipe" to instrukcja dla karuzeli. Reel się ogląda, nie przewija. */
        ${scope} .swipe { display: none; }
      `}</style>

      {/* Cień odcina slajd od podkładu. Podkład to ta sama skórka rozciągnięta do
          1920, więc gradient tła ma tam inną skalę niż w kanwie – bez cienia szew
          potrafi się zaznaczyć jako delikatny uskok jasności. */}
      <div
        className={`reel-slide reel-slide-${index}`}
        style={{
          width: canvas.width,
          height: canvas.height,
          boxShadow: "0 40px 120px rgba(0,0,0,0.45)",
        }}
        dangerouslySetInnerHTML={{ __html: html }}
      />
    </AbsoluteFill>
  );
};
