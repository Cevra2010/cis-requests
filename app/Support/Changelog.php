<?php

namespace App\Support;

/**
 * Liest die Versionsnummer (VERSION-Datei) und das Änderungsprotokoll
 * (CHANGELOG.md) des Repos – beide sind Klartext-Dateien im Repo-Root,
 * git-versioniert, reisen also automatisch mit jedem "git pull" mit.
 * Bewusst kein Aufruf von "git log" zur Laufzeit (unabhängig davon, ob
 * exec()/shell_exec() auf dem Produktivserver erlaubt ist) – das Changelog
 * wird stattdessen je Release von Hand kurz und verständlich gepflegt.
 */
class Changelog
{
    public static function currentVersion(): string
    {
        $path = base_path('VERSION');

        return is_file($path) ? trim(file_get_contents($path)) : '0.0.0.0';
    }

    /**
     * Alle Changelog-Abschnitte, deren Version größer ist als $seenVersion
     * (absteigend nach Version, neuestes zuerst). $seenVersion === null gilt
     * als "niedriger als jede vorhandene Version".
     *
     * @return array<int, array{version: string, date: ?string, items: array<int, string>}>
     */
    public static function entriesSince(?string $seenVersion): array
    {
        $sections = self::parse();

        return array_values(array_filter(
            $sections,
            fn (array $section) => self::compareVersions($section['version'], $seenVersion) > 0
        ));
    }

    /**
     * @return array<int, array{version: string, date: ?string, items: array<int, string>}>
     */
    private static function parse(): array
    {
        $path = base_path('CHANGELOG.md');
        if (! is_file($path)) {
            return [];
        }

        $lines    = preg_split('/\r?\n/', file_get_contents($path));
        $sections = [];
        $current  = null;

        foreach ($lines as $line) {
            if (preg_match('/^##\s+([0-9.]+)\s*(?:—|-)?\s*(\d{4}-\d{2}-\d{2})?/u', $line, $m)) {
                if ($current) {
                    $sections[] = $current;
                }
                $current = ['version' => $m[1], 'date' => $m[2] ?? null, 'items' => []];
                continue;
            }
            if ($current && preg_match('/^-\s+(.+)$/', $line, $m)) {
                $current['items'][] = trim($m[1]);
            }
        }
        if ($current) {
            $sections[] = $current;
        }

        usort($sections, fn ($a, $b) => self::compareVersions($b['version'], $a['version']));

        return $sections;
    }

    /**
     * Vergleicht zwei Versionsnummern im Format HAUPT.UNTER.PATCH.HOTFIX
     * Segment für Segment, numerisch. null gilt als niedriger als alles.
     * Rückgabe wie strcmp: <0, 0, >0.
     */
    public static function compareVersions(?string $a, ?string $b): int
    {
        if ($a === $b) {
            return 0;
        }
        if ($a === null) {
            return -1;
        }
        if ($b === null) {
            return 1;
        }

        $partsA = array_pad(explode('.', $a), 4, '0');
        $partsB = array_pad(explode('.', $b), 4, '0');

        for ($i = 0; $i < 4; $i++) {
            $diff = ((int) ($partsA[$i] ?? 0)) <=> ((int) ($partsB[$i] ?? 0));
            if ($diff !== 0) {
                return $diff;
            }
        }

        return 0;
    }
}
