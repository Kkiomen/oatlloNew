import { useCurrentFrame } from "remotion";
import type { SceneProps } from "../types";
import { COLORS, MONO_STYLE, reveal, rise } from "../theme";
import { codeLines, str } from "../params";

/**
 * Atrapa terminala: komenda "wpisuje się" (typewriter po znakach), a output
 * pojawia się pod spodem po jej dopisaniu. `code` = komenda; `output` = wynik.
 */
export const Terminal: React.FC<SceneProps> = ({ scene, accent }) => {
  const frame = useCurrentFrame();
  const command = codeLines(scene).join("\n");
  const output = str(scene, "output");

  // Typewriter: liczba widocznych znaków rośnie liniowo w oknie 10..46 klatek.
  const typed = Math.floor(
    reveal(frame, 10, 36) * command.length,
  );
  const shown = command.slice(0, typed);
  const done = typed >= command.length;

  return (
    <div
      style={{
        borderRadius: 28,
        background: "#0c0c0c",
        border: `1px solid ${COLORS.rule}`,
        overflow: "hidden",
        boxShadow: "0 40px 120px rgba(0,0,0,0.5)",
        ...MONO_STYLE,
        ...rise(frame, 2, 24),
      }}
    >
      <div
        style={{
          display: "flex",
          gap: 16,
          padding: "26px 34px",
          borderBottom: `1px solid ${COLORS.rule}`,
        }}
      >
        {["#ff5f56", "#ffbd2e", "#27c93f"].map((c) => (
          <span
            key={c}
            style={{ width: 22, height: 22, borderRadius: 999, background: c }}
          />
        ))}
      </div>

      <div style={{ padding: "40px 44px", fontSize: 44, lineHeight: 1.5 }}>
        <div style={{ display: "flex", whiteSpace: "pre-wrap" }}>
          <span style={{ color: accent, marginRight: 20 }}>$</span>
          <span style={{ color: COLORS.ink }}>{shown}</span>
          {!done ? (
            <span style={{ color: accent, opacity: frame % 30 < 15 ? 1 : 0 }}>▋</span>
          ) : null}
        </div>

        {output !== "" ? (
          // Output pojawia się po zakończeniu typewritera (okno 10..46), więc
          // odsłaniamy go od klatki 50 — i tylko gdy komenda już się dopisała.
          <div
            style={{
              marginTop: 28,
              color: COLORS.muted,
              whiteSpace: "pre-wrap",
              opacity: done ? reveal(frame, 50, 12) : 0,
            }}
          >
            {output}
          </div>
        ) : null}
      </div>
    </div>
  );
};
