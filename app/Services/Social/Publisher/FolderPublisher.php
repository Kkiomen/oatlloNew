<?php

namespace App\Services\Social\Publisher;

use App\Services\Social\Export\SocialExportResult;
use App\Services\Social\SocialPost;
use App\Services\Social\SocialPostType;

/**
 * "Publikacja" v1: post ląduje w folderze, a człowiek wrzuca go ręcznie.
 *
 * Ta klasa CELOWO niczego nie wysyła. Istnieje po to, żeby `social:publish`
 * miało sens już dziś i żeby dołożenie Graph API było podmianą jednej pozycji
 * w config/social.php, a nie nową komendą i nowym nawykiem.
 */
class FolderPublisher implements SocialPublisher
{
    public function name(): string
    {
        return 'folder (ręczny upload)';
    }

    public function supports(SocialPostType $type): bool
    {
        return true;
    }

    public function publish(SocialPost $post, SocialExportResult $export): SocialPublishResult
    {
        $files = implode(', ', array_map('basename', $export->imagePaths));

        $instructions = [
            "Otwórz folder: {$export->directory}",
            $post->type === SocialPostType::Story
                ? 'Dodaj jako Story: 01.png'
                : "Dodaj slajdy W TEJ KOLEJNOŚCI: {$files}",
            "Wklej podpis z: {$export->captionPath}",
        ];

        // Notatka autora idzie tu, a NIE do caption.txt: to jedyne kroki, których
        // renderer nie zrobi (ankiety, naklejki, cluster ze story), więc ich
        // miejsce jest w instrukcji dla człowieka, a nie w tekście do wklejenia.
        if ($post->hasNotes()) {
            foreach (preg_split('/\R/', trim($post->notes)) ?: [] as $line) {
                if (trim($line) !== '') {
                    $instructions[] = $line;
                }
            }
        }

        if ($post->link !== null) {
            $instructions[] = "Upewnij się, że link w bio wskazuje na: {$post->link}";
        }

        if ($post->publishAt !== null) {
            $instructions[] = 'Zaplanowany termin (tylko notatka, nic go nie pilnuje): '
                . $post->publishAt->format('Y-m-d H:i');
        }

        $instructions[] = "Po wrzuceniu zmień `status:` na `published` w resources/social/{$post->slug}.md";

        return SocialPublishResult::manual(
            publisher: $this->name(),
            summary: "Post '{$post->slug}' ({$export->slideCount()} slajd(ów)) czeka na ręczny upload.",
            instructions: $instructions,
        );
    }
}
