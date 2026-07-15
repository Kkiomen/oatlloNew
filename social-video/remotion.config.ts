/**
 * Konfiguracja Remotiona dla Reelsów Oatllo.
 *
 * Tailwinda tu NIE MA i być nie może: grafiki social stylują się własnym CSS-em
 * wklejanym razem z HTML-em slajdu (resources/views/social), więc moduł nigdy nie
 * wymaga `npm run css:public`. Scaffold `create-video` dokłada Tailwinda nawet przy
 * --no-tailwind – został wyrzucony ręcznie.
 */

import { Config } from "@remotion/cli/config";

Config.setVideoImageFormat("jpeg");
Config.setOverwriteOutput(true);
