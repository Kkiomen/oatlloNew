import { AbsoluteFill, Sequence, interpolate, useCurrentFrame } from "remotion";
import { Slide } from "./Slide";
import type { ReelProps } from "./types";

/**
 * Reel = slajdy posta jeden po drugim na kanwie 9:16.
 *
 * Kanwa Instagrama to 1080x1920, a slajd ma 1080x1350 – i slajd ZOSTAJE w 4:5.
 * Kusi, żeby rozciągnąć `.canvas` do pełnych 1920 (flexowy `.stage` sam by się
 * rozłożył), ale cały moduł jest kalibrowany pod 4:5: budżety lintu liczono dla
 * tej kanwy, a `.story-footer` kotwiczy się do geometrii `.stage` i rozjeżdża się
 * przy jej zmianie (patrz CLAUDE.md, miny w skórkach). Więc: slajd na podkładzie.
 *
 * Podkład to TEN SAM HTML rozciągnięty do 1920 z ukrytą treścią – dzięki temu tło
 * maluje sama skórka, razem ze swoimi zmiennymi. Nawet `spotlight`, którego tłem
 * jest `var(--accent)`, trafia tu bez jednej linijki w manifeście.
 *
 * Slajd ma pełną SZEROKOŚĆ kadru, więc typografia czyta się na telefonie dokładnie
 * tak jak w karuzeli w feedzie – letterbox dokłada tylko pasy, niczego nie zmniejsza.
 */

const REEL_W = 1080;
const REEL_H = 1920;
const SLIDE_H = 1350;

/**
 * Slajd siedzi WYŻEJ niż środek kadru: dolne ~390px zasłania UI Instagrama
 * (podpis, dźwięk, przyciski), więc wyśrodkowanie wsadziłoby stopkę slajdu pod
 * interfejs. Górny pas zostaje wąski, ale mieści pasek postępu.
 */
const SLIDE_TOP = 170;
const SLIDE_SHIFT_Y = SLIDE_TOP - (REEL_H - SLIDE_H) / 2;

/**
 * Segmentowy pasek postępu – jeden segment na slajd, jak w Stories.
 *
 * Rysujemy go SAMI, w pasie letterboxa. Kusiło, żeby przejąć `.bar` (pasek akcentu
 * na górze kanwy – element już tam jest i ma dokładnie tę rolę), ale skórki
 * traktują go jako swoją dekorację i biją nas specyficznością `.canvas.style-x .bar`
 * (0,3,0): `aurora` maluje go własnym gradientem, `card` w ogóle go chowa,
 * `editorial` ścina do 3px, `brutalist` rozdyma do 18px. Wyszłaby wieczna walka
 * z dziesięcioma skórkami, a każda nowa po cichu psułaby postęp.
 *
 * Pas letterboxa jest NASZ – nie sięga tam żaden CSS skórki.
 */
const Progress: React.FC<{ count: number; progress: number; accent: string }> = ({
  count,
  progress,
  accent,
}) => (
  <div
    style={{
      position: "absolute",
      top: SLIDE_TOP - 30,
      left: 60,
      right: 60,
      height: 8,
      display: "flex",
      gap: 8,
    }}
  >
    {Array.from({ length: count }, (_, i) => (
      <div
        key={i}
        style={{
          flex: 1,
          borderRadius: 999,
          // Tor z akcentu na niskiej alfie, nie z bieli ani szarości: Reel bywa na
          // jasnej skórce (paper, brutalist), gdzie biały tor byłby niewidoczny.
          background: `color-mix(in srgb, ${accent} 25%, transparent)`,
          overflow: "hidden",
        }}
      >
        <div
          style={{
            height: "100%",
            borderRadius: 999,
            background: accent,
            // Segment wypełnia się dopiero, gdy postęp go dosięgnie.
            width: `${interpolate(progress * count, [i, i + 1], [0, 100], {
              extrapolateLeft: "clamp",
              extrapolateRight: "clamp",
            })}%`,
          }}
        />
      </div>
    ))}
  </div>
);

const Backdrop: React.FC<{ html: string }> = ({ html }) => (
  <>
    <style>{`
      .reel-backdrop .canvas { height: ${REEL_H}px; }

      /* Z podkładu zostaje SAMO tło skórki. Treść i watermark są w slajdzie na
         wierzchu, a poświaty muszą zniknąć: podkład ma inną wysokość niż kanwa,
         więc jego poświaty nie trafiłyby w te ze slajdu i szew zaznaczyłby się
         dokładnie tam, gdzie ma go nie być. */
      .reel-backdrop .stage,
      .reel-backdrop .watermark,
      .reel-backdrop .glow,
      .reel-backdrop .bar { display: none; }
    `}</style>
    <AbsoluteFill
      className="reel-backdrop"
      dangerouslySetInnerHTML={{ __html: html }}
    />
  </>
);

export const Reel: React.FC<ReelProps> = ({ manifest, html }) => {
  const frame = useCurrentFrame();

  if (!manifest) {
    throw new Error(
      "Brak manifestu Reela. Odpal `php artisan social:video {slug}` – to on buduje " +
        "public/slides/{slug}/reel.json wraz z HTML-em slajdów.",
    );
  }

  const total = manifest.slides.reduce((sum, s) => sum + s.durationInFrames, 0);

  // `story` jest rysowane od razu na 1080x1920 (ma własne, ogromne marginesy pod
  // UI Instagrama – patrz canvasMetrics w SocialImageService). Taki slajd wypełnia
  // kadr sam, więc podkład tylko by go dublował.
  const fullBleed = manifest.canvas.height === REEL_H;

  let elapsed = 0;

  return (
    <AbsoluteFill style={{ width: REEL_W, height: REEL_H }}>
      {manifest.slides.map((slide, i) => {
        const from = elapsed;
        elapsed += slide.durationInFrames;

        return (
          <Sequence
            key={slide.file}
            name={`Slajd ${i + 1}`}
            from={from}
            durationInFrames={slide.durationInFrames}
          >
            {fullBleed ? null : <Backdrop html={html[i]} />}
            <AbsoluteFill
              style={{
                translate: fullBleed ? undefined : `0 ${SLIDE_SHIFT_Y}px`,
              }}
            >
              <Slide
                index={i}
                slide={slide}
                html={html[i]}
                canvas={manifest.canvas}
              />
            </AbsoluteFill>
          </Sequence>
        );
      })}

      {/* Postęp jest POZA <Sequence>: dotyczy całego Reela, więc liczy się z klatki
          bezwzględnej. Przy jednym slajdzie nie ma czego pokazywać, a full-bleed
          (story) nie ma pasa, w którym mógłby usiąść. */}
      {manifest.slides.length > 1 && !fullBleed ? (
        <Progress
          count={manifest.slides.length}
          progress={frame / total}
          accent={manifest.accent}
        />
      ) : null}
    </AbsoluteFill>
  );
};
