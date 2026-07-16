import { useCurrentFrame } from "remotion";
import type { SceneProps } from "../types";
import { COLORS, reveal, rise } from "../theme";
import { str } from "../params";

/**
 * Liczba/metryka jako bohater ("101 to 2", "100x faster"). Wielki tekst akcentem,
 * z lekkim "pop" skalowaniem na wejściu. Opcjonalny podpis (`label`).
 */
export const Callout: React.FC<SceneProps> = ({ scene, accent }) => {
  const frame = useCurrentFrame();
  const text = str(scene, "text");
  const label = str(scene, "label");

  const pop = reveal(frame, 4, 20);
  const size = text.length > 12 ? 168 : text.length > 7 ? 220 : 280;

  return (
    <div style={{ textAlign: "center" }}>
      <div
        style={{
          fontSize: size,
          fontWeight: 800,
          letterSpacing: "-0.03em",
          lineHeight: 1,
          color: accent,
          opacity: pop,
          transform: `scale(${0.8 + pop * 0.2})`,
        }}
      >
        {text}
      </div>
      {label !== "" ? (
        <div
          style={{
            marginTop: 40,
            fontSize: 52,
            fontWeight: 600,
            color: COLORS.muted,
            ...rise(frame, 16, 24),
          }}
        >
          {label}
        </div>
      ) : null}
    </div>
  );
};
