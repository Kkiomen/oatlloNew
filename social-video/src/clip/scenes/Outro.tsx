import { useCurrentFrame } from "remotion";
import type { SceneProps } from "../types";
import { COLORS, reveal, rise } from "../theme";
import { str } from "../params";

/**
 * Zamknięcie: CTA (domena) + zachęta do obserwowania. Akcentowana "pigułka"
 * z linkiem, pulsująca subtelnie, żeby przyciągnąć wzrok na końcu.
 */
export const Outro: React.FC<SceneProps> = ({ scene, accent }) => {
  const frame = useCurrentFrame();
  const cta = str(scene, "cta", "oatllo.com");
  const line = str(scene, "text", "Follow for more");

  const pulse = 1 + Math.sin(frame / 12) * 0.02;

  return (
    <div style={{ textAlign: "center" }}>
      <div
        style={{
          fontSize: 72,
          fontWeight: 700,
          color: COLORS.muted,
          marginBottom: 56,
          ...rise(frame, 2, 26),
        }}
      >
        {line}
      </div>

      <div
        style={{
          display: "inline-block",
          padding: "34px 72px",
          borderRadius: 999,
          background: accent,
          color: "#0a0a0a",
          fontSize: 84,
          fontWeight: 800,
          letterSpacing: "-0.01em",
          opacity: reveal(frame, 12, 20),
          transform: `scale(${pulse})`,
        }}
      >
        {cta}
      </div>
    </div>
  );
};
