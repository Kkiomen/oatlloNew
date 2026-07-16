import type { SceneProps } from "./types";
import { Title } from "./scenes/Title";
import { Statement } from "./scenes/Statement";
import { Callout } from "./scenes/Callout";
import { Bullets } from "./scenes/Bullets";
import { CodeReveal } from "./scenes/CodeReveal";
import { Terminal } from "./scenes/Terminal";
import { Compare } from "./scenes/Compare";
import { Outro } from "./scenes/Outro";

/**
 * Mapa typ sceny -> komponent. Dopisanie sceny = dodanie komponentu tutaj +
 * wpisanie typu do config('clip.scene_types') w Laravelu. Klucze MUSZĄ się
 * zgadzać z tamtą listą — inaczej lint przepuści typ, którego render nie zna.
 */
export const SCENE_REGISTRY: Record<string, React.FC<SceneProps>> = {
  title: Title,
  statement: Statement,
  callout: Callout,
  bullets: Bullets,
  "code-reveal": CodeReveal,
  terminal: Terminal,
  compare: Compare,
  outro: Outro,
};
