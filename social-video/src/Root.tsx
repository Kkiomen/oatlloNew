import { CalculateMetadataFunction, Composition, staticFile } from "remotion";
import { Reel } from "./Reel";
import type { ReelManifest, ReelProps } from "./types";

/**
 * Jedna kompozycja `Reel`, parametryzowana slugiem.
 *
 * Nie ma tu listy postów, bo `registerRoot` jest synchroniczne, a slugi żyją na
 * dysku. Slug podaje CLI (`--props`), a `social:video` robi to za nas. W Studiu
 * slug zmienia się w panelu propsów.
 */

const calculateMetadata: CalculateMetadataFunction<ReelProps> = async ({
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

  // HTML slajdów wczytujemy TU, a nie w komponencie. calculateMetadata jest
  // async z definicji, więc render startuje z gotową treścią i nie trzeba
  // żonglować delayRender per slajd. Przy okazji props zostają małe – przez CLI
  // idzie sam slug, a nie ~160 KB dokumentu na slajd.
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

export const RemotionRoot: React.FC = () => {
  return (
    <Composition
      id="Reel"
      component={Reel}
      // Wymiary są stałe (pion Instagrama), resztę ustala calculateMetadata.
      width={1080}
      height={1920}
      fps={30}
      durationInFrames={300}
      defaultProps={{ slug: "eloquent-n1-carousel", manifest: null, html: [] }}
      calculateMetadata={calculateMetadata}
    />
  );
};
