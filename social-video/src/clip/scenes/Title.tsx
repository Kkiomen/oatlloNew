import { useCurrentFrame } from "remotion";
import type { SceneProps } from "../types";
import { reveal, rise } from "../theme";
import { str } from "../params";

/**
 * Hook clipa: wielki nagłówek + rozjeżdżające się podkreślenie akcentem.
 * Typografia skaluje się z długości tekstu, żeby długi headline nie wyjechał.
 */
export const Title: React.FC<SceneProps> = ({ scene, accent }) => {
  const frame = useCurrentFrame();
  const text = str(scene, "text");

  const size = text.length > 28 ? 116 : text.length > 16 ? 148 : 176;

  return (
    <div>
      <h1
        style={{
          margin: 0,
          fontSize: size,
          lineHeight: 1.02,
          fontWeight: 800,
          letterSpacing: "-0.02em",
          ...rise(frame, 4, 36),
        }}
      >
        {text}
      </h1>
      <div
        style={{
          marginTop: 40,
          height: 16,
          width: 220,
          borderRadius: 999,
          background: accent,
          transformOrigin: "left center",
          transform: `scaleX(${reveal(frame, 16)})`,
        }}
      />
    </div>
  );
};
