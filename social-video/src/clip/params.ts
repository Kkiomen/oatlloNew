import type { ClipScene } from "./types";

/**
 * Bezpieczne czytanie pól sceny z manifestu. `params` to `Record<string, unknown>`
 * (PHP przepuszcza je jak leci), więc każdy odczyt broni się przed brakiem/typem —
 * scena bez pola ma renderować pustkę, nie wywalać render.
 */

export const str = (scene: ClipScene, key: string, fallback = ""): string => {
  const v = scene.params[key];

  return typeof v === "string" || typeof v === "number" ? String(v) : fallback;
};

export const list = (scene: ClipScene, key: string): string[] => {
  const v = scene.params[key];

  if (!Array.isArray(v)) {
    return [];
  }

  return v.filter((x) => typeof x === "string" || typeof x === "number").map(String);
};

export const numList = (scene: ClipScene, key: string): number[] => {
  const v = scene.params[key];

  if (!Array.isArray(v)) {
    return [];
  }

  return v.filter((x) => typeof x === "number").map(Number);
};

/** Kod sceny jako linie (bez końcowych pustych). */
export const codeLines = (scene: ClipScene): string[] => {
  const code = str(scene, "code");

  if (code === "") {
    return [];
  }

  return code.replace(/\s+$/, "").split("\n");
};
