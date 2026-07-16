import { CalculateMetadataFunction, Composition, staticFile } from "remotion";
import { Reel } from "./Reel";
import type { ReelManifest, ReelProps } from "./types";
import { Clip } from "./clip/Clip";
import type { ClipManifest, ClipProps } from "./clip/types";

/**
 * Dwie kompozycje, obie parametryzowane slugiem:
 *  - Reel  — niema karuzela w ruchu (wstrzyknięty HTML z Blade),
 *  - Clip  — narrowane wideo (natywne sceny z biblioteki + głos + napisy).
 *
 * Nie ma tu listy postów, bo `registerRoot` jest synchroniczne, a slugi żyją na
 * dysku. Slug podaje CLI (`--props`); w Studiu zmienia się w panelu propsów.
 */

const calculateReelMetadata: CalculateMetadataFunction<ReelProps> = async ({
  props,
}) => {
  const manifest: ReelManifest = await fetch(
    staticFile(`slides/${props.slug}/reel.json`),
  ).then((res) => {
    if (!res.ok) {
      throw new Error(
        `Nie znaleziono manifestu dla "${props.slug}". Odpal najpierw: ` +
          `php artisan social:video ${props.slug}`,
      );
    }

    return res.json();
  });

  const html = await Promise.all(
    manifest.slides.map((slide) =>
      fetch(staticFile(`slides/${props.slug}/${slide.file}`)).then((res) =>
        res.text(),
      ),
    ),
  );

  return {
    props: { ...props, manifest, html },
    durationInFrames: manifest.slides.reduce(
      (sum, s) => sum + s.durationInFrames,
      0,
    ),
    fps: manifest.fps,
  };
};

const calculateClipMetadata: CalculateMetadataFunction<ClipProps> = async ({
  props,
}) => {
  const manifest: ClipManifest = await fetch(
    staticFile(`clips/${props.slug}/clip.json`),
  ).then((res) => {
    if (!res.ok) {
      throw new Error(
        `Nie znaleziono manifestu clipa dla "${props.slug}". Odpal najpierw: ` +
          `php artisan clip:stage ${props.slug}`,
      );
    }

    return res.json();
  });

  return {
    props: { ...props, manifest },
    durationInFrames: manifest.scenes.reduce(
      (sum, s) => sum + s.durationInFrames,
      0,
    ),
    fps: manifest.fps,
    width: manifest.canvas.width,
    height: manifest.canvas.height,
  };
};

export const RemotionRoot: React.FC = () => {
  return (
    <>
      <Composition
        id="Reel"
        component={Reel}
        width={1080}
        height={1920}
        fps={30}
        durationInFrames={300}
        defaultProps={{ slug: "eloquent-n1-carousel", manifest: null, html: [] }}
        calculateMetadata={calculateReelMetadata}
      />

      <Composition
        id="Clip"
        component={Clip}
        // Pion Instagrama/TikToka/Shorts; resztę ustala calculateMetadata.
        width={1080}
        height={1920}
        fps={30}
        durationInFrames={300}
        defaultProps={{ slug: "eloquent-n1-explainer", manifest: null }}
        calculateMetadata={calculateClipMetadata}
      />
    </>
  );
};
