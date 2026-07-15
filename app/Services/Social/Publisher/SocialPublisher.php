<?php

namespace App\Services\Social\Publisher;

use App\Services\Social\Export\SocialExportResult;
use App\Services\Social\SocialPost;
use App\Services\Social\SocialPostType;

/**
 * Szew pod publikację. Dziś jedyną implementacją jest FolderPublisher (eksport
 * + checklista ręcznego wrzucenia); docelowo dojdzie InstagramGraphPublisher.
 *
 * UCZCIWE OSTRZEŻENIE: podmiana tej klasy to NAJMNIEJSZA część roboty przy
 * Graph API. API nie przyjmuje plików lokalnych – wymaga publicznych URL-i HTTPS
 * do obrazków, konta Business, powiązanej strony na Facebooku i długożyciowego
 * tokenu. Ten interfejs oszczędza przepisywanie modułu, a nie tamten setup.
 */
interface SocialPublisher
{
    public function name(): string;

    public function supports(SocialPostType $type): bool;

    public function publish(SocialPost $post, SocialExportResult $export): SocialPublishResult;
}
