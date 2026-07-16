/**
 * Kontrakt między Laravelem a Remotionem dla CLIPÓW (narrowanych wideo).
 *
 * Manifest pisze PHP (`php artisan clip:stage`), czyta Remotion. Podział ról jest
 * sztywny: PHP wie WSZYSTKO o treści (ile scen, jaki typ, jaki akcent, ILE TRWA
 * scena — a to wynika z długości narracji), Remotion wie tylko, jak to poruszyć.
 *
 * To OSOBNY kontrakt od reela (types.ts w src/): reel wstrzykuje żywy HTML z Blade,
 * clip renderuje natywne komponenty scen z biblioteki. Dwa różne gatunki wideo.
 */

/** Jedno słowo narracji z timestampem — z tego powstają napisy karaoke. */
export type CaptionWord = {
  text: string;
  start: number;
  end: number;
};

export type Narration = {
  /** Ścieżka audio względem katalogu clipa (np. "audio/01.wav"). */
  audio: string;
  /** Klatek ciszy PRZED głosem (wizual zdąży wjechać). */
  leadIn: number;
  duration: number;
  words: CaptionWord[];
} | null;

export type SfxCue = {
  /** Ścieżka względem public/ (np. "sfx/whoosh.mp3"). */
  file: string;
  atFrame: number;
};

export type ClipScene = {
  /** Typ sceny — mapuje na komponent z registry (title, code-reveal, …). */
  type: string;
  durationInFrames: number;
  /** Pola zależne od typu (text, code, lang, items, cta…). */
  params: Record<string, unknown>;
  narration: Narration;
  sfx: SfxCue[];
};

export type ClipManifest = {
  slug: string;
  title: string;
  fps: number;
  canvas: { width: number; height: number };
  /** Akcent (hex) + klucz logo technologii (null => bez logo). */
  theme: { accent: string; logo: string | null };
  captions: { enabled: boolean; mode: string };
  /** Ścieżka podkładu względem public/ lub null. */
  music: string | null;
  scenes: ClipScene[];
};

export type ClipProps = {
  slug: string;
  /** Wypełniane przez calculateMetadata — w defaultProps zawsze null. */
  manifest: ClipManifest | null;
};

/** Propsy, które dostaje każdy komponent sceny z biblioteki. */
export type SceneProps = {
  scene: ClipScene;
  accent: string;
  /** Indeks sceny — scopuje ewentualny globalny CSS (patrz mina #2 w reelach). */
  index: number;
};
