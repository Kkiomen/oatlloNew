import { useCurrentFrame } from "remotion";
import type { SceneProps } from "../types";
import { COLORS, MONO_STYLE, codeFontSize, rise } from "../theme";
import { codeLines, numList, str } from "../params";

/**
 * Blok kodu w "oknie". Linie odsłaniają się od góry (stagger), a linie z
 * `highlight: [n]` dostają akcentowaną krawędź i podświetlenie — wzrok idzie
 * prosto do sedna. Font mono, budżet 46 kolumn pilnuje lint (tu tylko renderujemy).
 */
export const CodeReveal: React.FC<SceneProps> = ({ scene, accent }) => {
  const frame = useCurrentFrame();
  const lines = codeLines(scene);
  const highlight = new Set(numList(scene, "highlight"));
  const lang = str(scene, "lang");

  // Panel ma pełną szerokość strefy treści (1080 - 2*96) minus paddingi okna
  // i rynna numeru linii => ~760px na sam kod.
  const longest = lines.reduce((m, l) => Math.max(m, l.length), 0);
  const fontSize = codeFontSize(longest, 760);

  return (
    <div
      style={{
        borderRadius: 28,
        background: COLORS.panel,
        border: `1px solid ${COLORS.rule}`,
        overflow: "hidden",
        boxShadow: "0 40px 120px rgba(0,0,0,0.5)",
        ...rise(frame, 2, 24),
      }}
    >
      {/* Pasek okna: kropki + język. */}
      <div
        style={{
          display: "flex",
          alignItems: "center",
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
        {lang !== "" ? (
          <span
            style={{
              marginLeft: "auto",
              ...MONO_STYLE,
              fontSize: 30,
              color: COLORS.muted,
            }}
          >
            {lang}
          </span>
        ) : null}
      </div>

      <div style={{ padding: "36px 40px", ...MONO_STYLE, fontSize, lineHeight: 1.5 }}>
        {lines.map((line, i) => {
          const lit = highlight.has(i + 1);

          return (
            <div
              key={i}
              style={{
                display: "flex",
                whiteSpace: "pre",
                padding: "4px 16px",
                margin: "0 -16px",
                borderLeft: `6px solid ${lit ? accent : "transparent"}`,
                background: lit ? `${accent}1f` : "transparent",
                color: lit ? COLORS.ink : COLORS.muted,
                ...rise(frame, 14 + i * 6, 18),
              }}
            >
              <span style={{ color: COLORS.faint, width: 48, flex: "0 0 auto" }}>
                {i + 1}
              </span>
              <span>{line === "" ? " " : line}</span>
            </div>
          );
        })}
      </div>
    </div>
  );
};
