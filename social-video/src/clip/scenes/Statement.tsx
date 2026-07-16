import { useCurrentFrame } from "remotion";
import type { SceneProps } from "../types";
import { COLORS, rise } from "../theme";
import { str } from "../params";

/**
 * Jedno mocne zdanie. Akcentowany pierwszy znak "kropki" prowadzi wzrok;
 * reszta wjeżdża pod spodem.
 */
export const Statement: React.FC<SceneProps> = ({ scene, accent }) => {
  const frame = useCurrentFrame();
  const text = str(scene, "text");

  const size = text.length > 40 ? 92 : text.length > 22 ? 116 : 140;

  return (
    <div>
      <div
        style={{
          width: 64,
          height: 64,
          borderRadius: 18,
          background: accent,
          marginBottom: 44,
          ...rise(frame, 2, 20),
        }}
      />
      <p
        style={{
          margin: 0,
          fontSize: size,
          lineHeight: 1.08,
          fontWeight: 700,
          letterSpacing: "-0.01em",
          color: COLORS.ink,
          ...rise(frame, 8, 34),
        }}
      >
        {text}
      </p>
    </div>
  );
};
