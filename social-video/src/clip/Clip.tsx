import { AbsoluteFill, Audio, Sequence, staticFile } from "remotion";
import type { ClipProps, ClipScene } from "./types";
import { COLORS } from "./theme";
import { fontFaceCss, useFonts } from "./theme";
import { SceneFrame } from "./SceneFrame";
import { Captions } from "./Captions";
import { SCENE_REGISTRY } from "./registry";

/**
 * Clip = narrowane wideo: sceny jedna po drugiej na kanwie 9:16, każda z własną
 * narracją, napisami i (opcjonalnie) SFX.
 *
 * Podział ról: manifest (PHP) mówi CO i JAK DŁUGO, ten komponent tylko sekwencjonuje
 * i porusza. Długości scen są policzone z audio po stronie PHP — tu je tylko sumujemy.
 *
 * To OSOBNA kompozycja od Reela (ten wstrzykuje HTML z Blade; clip renderuje
 * natywne sceny z registry). Wspólny jest tylko projekt Node i font.
 */

/** Nieznany typ sceny nie może wywalić całego renderu — pokazujemy zaślepkę. */
const Missing: React.FC<{ type: string }> = ({ type }) => (
  <div style={{ color: COLORS.muted, fontSize: 48, textAlign: "center" }}>
    Brak komponentu sceny: <code>{type}</code>
  </div>
);

export const Clip: React.FC<ClipProps> = ({ slug, manifest }) => {
  useFonts();

  if (!manifest) {
    throw new Error(
      "Brak manifestu clipa. Odpal `php artisan clip:stage {slug}` — to on buduje " +
        "public/clips/{slug}/clip.json wraz z audio narracji.",
    );
  }

  const { fps, theme, captions } = manifest;
  let elapsed = 0;

  return (
    <AbsoluteFill style={{ background: COLORS.bg }}>
      <style>{fontFaceCss()}</style>

      {manifest.scenes.map((scene: ClipScene, i: number) => {
        const from = elapsed;
        elapsed += scene.durationInFrames;

        const Comp = SCENE_REGISTRY[scene.type];
        const leadIn = scene.narration?.leadIn ?? 0;

        return (
          <Sequence
            key={i}
            name={`${i + 1} · ${scene.type}`}
            from={from}
            durationInFrames={scene.durationInFrames}
          >
            <SceneFrame accent={theme.accent}>
              {Comp ? (
                <Comp scene={scene} accent={theme.accent} index={i} />
              ) : (
                <Missing type={scene.type} />
              )}
            </SceneFrame>

            {/* Napisy nad marką/UI. Czas liczą z timestampów, z offsetem leadIn. */}
            {captions.enabled && scene.narration ? (
              <Captions
                words={scene.narration.words}
                leadIn={leadIn}
                fps={fps}
                accent={theme.accent}
                mode={captions.mode}
              />
            ) : null}

            {/* Głos: startuje po leadIn (wizual zdąży wjechać). */}
            {scene.narration ? (
              <Sequence from={leadIn} name="narration">
                <Audio src={staticFile(`clips/${slug}/${scene.narration.audio}`)} />
              </Sequence>
            ) : null}

            {/* SFX w wyznaczonych klatkach sceny. */}
            {scene.sfx.map((cue, k) => (
              <Sequence key={k} from={cue.atFrame} name={`sfx ${k + 1}`}>
                <Audio src={staticFile(cue.file)} />
              </Sequence>
            ))}
          </Sequence>
        );
      })}
    </AbsoluteFill>
  );
};
