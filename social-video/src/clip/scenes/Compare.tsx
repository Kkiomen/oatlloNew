import { useCurrentFrame } from "remotion";
import type { SceneProps } from "../types";
import { COLORS, MONO_STYLE, rise } from "../theme";
import { str } from "../params";

/**
 * Split "źle / dobrze": dwie karty jedna nad drugą (kadr jest pionowy, więc
 * pion czyta się lepiej niż lewa/prawa). Lewa wjeżdża z lewej, prawa z prawej.
 * Pola: `bad` / `good` (tekst lub kod), opcjonalnie `bad_label` / `good_label`.
 */
const Card: React.FC<{
  label: string;
  body: string;
  tone: string;
  frame: number;
  start: number;
  dir: number;
}> = ({ label, body, tone, frame, start, dir }) => {
  const r = rise(frame, start, 0);
  const shift = (1 - (r.opacity as number)) * 60 * dir;

  return (
    <div
      style={{
        flex: 1,
        borderRadius: 24,
        background: COLORS.panel,
        border: `1px solid ${COLORS.rule}`,
        borderTop: `6px solid ${tone}`,
        padding: "34px 40px",
        opacity: r.opacity,
        transform: `translateX(${shift}px)`,
      }}
    >
      <div
        style={{
          fontSize: 40,
          fontWeight: 800,
          color: tone,
          marginBottom: 22,
          letterSpacing: "0.02em",
        }}
      >
        {label}
      </div>
      <div
        style={{
          ...MONO_STYLE,
          fontSize: 38,
          lineHeight: 1.45,
          color: COLORS.ink,
          whiteSpace: "pre-wrap",
        }}
      >
        {body}
      </div>
    </div>
  );
};

export const Compare: React.FC<SceneProps> = ({ scene }) => {
  const frame = useCurrentFrame();

  return (
    <div style={{ display: "flex", flexDirection: "column", gap: 40 }}>
      <Card
        label={str(scene, "bad_label", "Before")}
        body={str(scene, "bad")}
        tone="#f87171"
        frame={frame}
        start={4}
        dir={-1}
      />
      <Card
        label={str(scene, "good_label", "After")}
        body={str(scene, "good")}
        tone="#4ade80"
        frame={frame}
        start={14}
        dir={1}
      />
    </div>
  );
};
