<?php

namespace App\Services\Clip\Tts;

/**
 * Provider syntezy mowy. Za tym interfejsem stoi mock (cisza) albo ElevenLabs
 * (realny głos) — wybór przez config('clip.tts.driver').
 *
 * Cel podziału: pipeline renderuje wideo (niemo, z poprawnym timingiem i napisami)
 * BEZ klucza ElevenLabs. Gdy klucz się pojawi, zmieniasz driver — Remotion,
 * stager, lint i cache zostają nietknięte.
 */
interface TtsProvider
{
    /**
     * Syntezuje jedną wypowiedź (narrację jednej sceny).
     *
     * @param string $text    Tekst do wypowiedzenia (scene->narration)
     * @param string $voiceId Identyfikator głosu (mock ignoruje)
     */
    public function synthesize(string $text, string $voiceId): SynthesizedSpeech;

    /**
     * Stabilny identyfikator providera — wchodzi do klucza cache'u, żeby zmiana
     * mock -> elevenlabs unieważniła wcześniejszą ciszę.
     */
    public function id(): string;
}
