import { useCurrentFrame } from "remotion";
import type { SceneProps } from "../types";
import { COLORS, rise } from "../theme";
import { list, str } from "../params";

/**
 * Lista punktów ze staggerem: każdy kolejny wjeżdża 10 klatek po poprzednim,
 * z akcentowanym kwadracikiem-znacznikiem. Opcjonalny nagłówek (`text`).
 */
export const Bullets: React.FC<SceneProps> = ({ scene, accent }) => {
  const frame = useCurrentFrame();
  const heading = str(scene, "text");
  const items = list(scene, "items");

  return (
    <div>
      {heading !== "" ? (
        <h2
          style={{
            margin: "0 0 56px",
            fontSize: 84,
            fontWeight: 800,
            letterSpacing: "-0.02em",
            ...rise(frame, 2, 24),
          }}
        >
          {heading}
        </h2>
      ) : null}

      <ul style={{ margin: 0, padding: 0, listStyle: "none" }}>
        {items.map((item, i) => (
          <li
            key={i}
            style={{
              display: "flex",
              alignItems: "center",
              gap: 36,
              marginBottom: 44,
              fontSize: 68,
              fontWeight: 600,
              lineHeight: 1.15,
              color: COLORS.ink,
              ...rise(frame, 12 + i * 10, 26),
            }}
          >
            <span
              style={{
                flex: "0 0 auto",
                width: 28,
                height: 28,
                borderRadius: 8,
                background: accent,
              }}
            />
            <span>{item}</span>
          </li>
        ))}
      </ul>
    </div>
  );
};
