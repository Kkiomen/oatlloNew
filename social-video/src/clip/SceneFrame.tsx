import { AbsoluteFill } from "remotion";
import { COLORS, FONT, glow, reveal } from "./theme";
import { useCurrentFrame } from "remotion";

/**
 * Wspólna ramka każdej sceny: ciemne tło, poświata akcentu, marka i STREFA
 * BEZPIECZNA. Treść siedzi w górnych ~1350px kadru — dolne ~470px zasłania UI
 * TikToka/Shorts/Reels (podpis, dźwięk, przyciski) i tam idą napisy. Ta sama
 * zasada co SLIDE_TOP w reelu: nie środkuj przez cały kadr 9:16.
 */
export const SceneFrame: React.FC<{
  accent: string;
  children: React.ReactNode;
}> = ({ accent, children }) => {
  const frame = useCurrentFrame();

  return (
    <AbsoluteFill
      style={{
        background: COLORS.bg,
        color: COLORS.ink,
        fontFamily: FONT,
      }}
    >
      <div style={{ ...glow(accent), opacity: reveal(frame, 0, 24) }} />

      {/* Strefa treści: górne ~1180px, spory margines boczny. Niżej jest pas
          napisów (Captions) i marka, a jeszcze niżej UI platformy — dlatego
          treść NIE środkuje się przez cały kadr 9:16. */}
      <div
        style={{
          position: "absolute",
          top: 140,
          left: 96,
          right: 96,
          height: 1040,
          display: "flex",
          flexDirection: "column",
          justifyContent: "center",
        }}
      >
        {children}
      </div>

      {/* Marka — mały uchwyt nad strefą UI platformy. */}
      <div
        style={{
          position: "absolute",
          top: 1400,
          left: 0,
          right: 0,
          textAlign: "center",
          fontSize: 30,
          fontWeight: 700,
          letterSpacing: "0.08em",
          color: COLORS.faint,
          opacity: reveal(frame, 8, 20),
        }}
      >
        @oatllo
      </div>
    </AbsoluteFill>
  );
};
