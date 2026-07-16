import { useCurrentFrame } from "remotion";
import type { CaptionWord } from "./types";
import { COLORS, FONT } from "./theme";

/**
 * Napisy karaoke — główny driver retencji na TikToku/Shorts. Aktualne słowo
 * podświetlone akcentem, reszta bieżącej linijki przygaszona.
 *
 * Czas liczymy z timestampów słów (sekundy od startu AUDIO), a audio startuje
 * `leadIn` klatek po początku sceny — więc przesuwamy o leadIn. Na mocku słowa
 * są rozłożone równo, więc karaoke działa i bez realnego głosu.
 *
 * Pas siedzi NAD marką i UI platformy (patrz strefa bezpieczna w SceneFrame).
 */
const CHUNK = 4; // ile słów na raz w linijce

export const Captions: React.FC<{
  words: CaptionWord[];
  leadIn: number;
  fps: number;
  accent: string;
  mode: string;
}> = ({ words, leadIn, fps, accent, mode }) => {
  const frame = useCurrentFrame();

  if (words.length === 0) {
    return null;
  }

  const t = (frame - leadIn) / fps; // sekundy w obrębie narracji

  // Indeks aktualnie mówionego słowa (albo ostatniego, gdy już po nim).
  let active = words.findIndex((w) => t >= w.start && t < w.end);
  if (active === -1) {
    active = t < words[0].start ? 0 : words.length - 1;
  }

  const chunkStart = Math.floor(active / CHUNK) * CHUNK;
  const chunk = words.slice(chunkStart, chunkStart + CHUNK);

  return (
    <div
      style={{
        position: "absolute",
        top: 1210,
        left: 80,
        right: 80,
        textAlign: "center",
        fontFamily: FONT,
        fontSize: 58,
        fontWeight: 800,
        lineHeight: 1.2,
        letterSpacing: "-0.01em",
        textShadow: "0 4px 24px rgba(0,0,0,0.6)",
      }}
    >
      {chunk.map((w, i) => {
        const globalIdx = chunkStart + i;
        const isActive = globalIdx === active;
        const spoken = t >= w.end;

        // block: aktywne słowo akcentem; karaoke: dodatkowo przygasza jeszcze
        // niewypowiedziane słowa, żeby oko szło za lektorem.
        const color = isActive
          ? accent
          : mode === "karaoke" && !spoken
            ? COLORS.faint
            : COLORS.ink;

        return (
          <span key={globalIdx} style={{ color, margin: "0 10px" }}>
            {w.text}
          </span>
        );
      })}
    </div>
  );
};
