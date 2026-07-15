/**
 * Kontrakt między Laravelem a Remotionem.
 *
 * Manifest pisze PHP (`php artisan social:video`), czyta Remotion. Podział jest
 * celowy: PHP wie WSZYSTKO o treści (ile slajdów, jaka skórka, jaki akcent, ile
 * czasu potrzebuje slajd na przeczytanie), Remotion wie tylko, jak to poruszyć.
 * Dzięki temu decyzje o treści nie uciekają do TSX-a, gdzie nie sięga ani lint,
 * ani testy PHP.
 */

export type ReelSlide = {
  /** Ścieżka do dokumentu HTML wewnątrz public/ (staticFile). */
  file: string;
  /** Ile klatek trzyma się ten slajd. Liczone z objętości treści po stronie PHP. */
  durationInFrames: number;
  /** Ile dzieci ma `.body` – potrzebne do staggeru wjazdu treści. */
  bodyChildren: number;
};

export type ReelManifest = {
  slug: string;
  type: string;
  /** Nazwa skórki (aurora, paper…). Wyłącznie do diagnozy "czemu to tak wygląda". */
  style: string;
  /** Kolor akcentu posta – pasek postępu Reela. */
  accent: string;
  /**
   * Kanwa slajdu. `story` ma natywnie 1080x1920 i wypełnia kadr Reela w całości;
   * pozostałe typy to 1080x1350 i lądują na podkładzie.
   */
  canvas: { width: number; height: number };
  fps: number;
  slides: ReelSlide[];
};

export type ReelProps = {
  slug: string;
  /** Wypełniane przez calculateMetadata – w defaultProps zawsze null. */
  manifest: ReelManifest | null;
  /** HTML każdego slajdu, wczytany w calculateMetadata. */
  html: string[];
};
