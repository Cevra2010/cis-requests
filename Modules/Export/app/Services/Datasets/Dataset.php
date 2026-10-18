<?php

namespace Modules\Export\Services\Datasets;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Export\Models\ExportTemplate;
use Modules\Export\Services\TenderExporter;

/**
 * Ein Systemdatensatz (z.B. Benutzer, Rollen, Projekte) für das Import-/Export-Werkzeug.
 *
 * Eine Vorlage wählt aus fields() ihre Spalten. Export: build(). Import: plan() prüft Zeilen
 * gegen die Datenbank ohne zu schreiben, apply() schreibt den Plan in einer Transaktion.
 * Zeilen werden über den Import-Key (Spalte "_ImportKey" = cis_row_id des Datensatzes) zugeordnet.
 *
 * Neue Datensätze: Unterklasse anlegen und in Datasets::all() eintragen. Feldschlüssel müssen über
 * alle Datensätze eindeutig sein (Präfix je Datensatz, z.B. "us_" für Benutzer).
 */
abstract class Dataset
{
    abstract public function key(): string;

    abstract public function label(): string;

    /**
     * @return array<string, array{label: string, origin: string, import?: bool, clearable?: bool}>
     *   origin: Schlüssel in ExportFieldRegistry::ORIGINS (Herkunft des Feldes);
     *   import: Feld kann beim Import geschrieben werden; clearable: eine leere Zelle leert den Wert
     *   (sonst bedeutet leer "nicht ändern").
     */
    abstract public function fields(): array;

    /** @return iterable<int, Model> */
    abstract protected function records(): iterable;

    abstract protected function exportValue(Model $record, string $field): string;

    /** Kann der Datensatz (z.B. wegen eines deaktivierten Moduls) gerade genutzt werden? */
    public function available(): bool
    {
        return true;
    }

    /** Dürfen Zeilen ohne Import-Key neue Datensätze anlegen? */
    public function canCreate(): bool
    {
        return false;
    }

    /** Felder, die für neue Datensätze ausgefüllt sein müssen. @return string[] */
    protected function requiredForCreate(): array
    {
        return [];
    }

    public function supportsImport(): bool
    {
        return collect($this->fields())->contains(fn (array $f) => ! empty($f['import']));
    }

    /** Spalten (Feldschlüssel) der mitgelieferten Standardvorlage. @return string[] */
    public function defaultColumns(): array
    {
        return array_keys($this->fields());
    }

    protected function findRecord(string $key): ?Model
    {
        return null;
    }

    protected function newRecord(): Model
    {
        throw new \LogicException('Dieser Datensatz kann nicht angelegt werden.');
    }

    protected function recordLabel(?Model $record, \Closure $get): string
    {
        return $record?->name ?? 'neuer Datensatz';
    }

    /**
     * Übersetzt eine Importzelle in eine Änderung oder null (keine Änderung). Wirft DatasetImportException bei ungültigem Wert.
     *
     * @param \Closure $get liefert den Rohwert einer anderen Spalte der Zeile (oder null, wenn die Vorlage sie nicht enthält)
     * @return array{label: string, old: string, new: string, set?: array<string, mixed>, after?: \Closure}|null
     *   set: Attribute des Datensatzes; after: Closure(Model): void für Beziehungen/Nebeneffekte nach dem Speichern.
     */
    protected function importField(?Model $record, string $field, string $raw, \Closure $get): ?array
    {
        return null;
    }

    // ── Export ──────────────────────────────────────────────────────────────

    /** @return array{headers: array<int, string>, rows: array<int, array<int, string>>, import_key_index: ?int} */
    public function build(ExportTemplate $template): array
    {
        $columns  = $template->columns;
        $headers  = $columns->pluck('label')->all();
        $withKey  = (bool) $template->include_import_key;
        $keyIndex = $withKey ? count($headers) : null;
        if ($withKey) {
            $headers[] = TenderExporter::IMPORT_KEY_HEADER;
        }

        $rows = [];
        foreach ($this->records() as $record) {
            $row = [];
            foreach ($columns as $column) {
                $row[] = $column->isFreeField() ? ($column->static_value ?? '') : (string) $this->exportValue($record, $column->field_key);
            }
            if ($withKey) {
                $row[] = (string) $record->cis_row_id;
            }
            $rows[] = $row;
        }

        return ['headers' => $headers, 'rows' => $rows, 'import_key_index' => $keyIndex];
    }

    // ── Import ──────────────────────────────────────────────────────────────

    /**
     * Prüft die eingelesenen Zeilen (ohne Kopfzeile), ohne zu schreiben.
     *
     * @param array<int, array<int, mixed>> $sheetRows
     * @return array<int, array{line: int, action: string, label: string, changes: array<string, array{0: string, 1: string}>, error: ?string, _ops: array}>
     *   action: create | update | unchanged | skipped | error
     */
    public function plan(ExportTemplate $template, array $sheetRows, bool $allowCreate): array
    {
        $columns  = $template->columns->values();
        $keyIndex = $columns->count();
        $fields   = $this->fields();

        $fieldIndex = [];
        foreach ($columns as $i => $column) {
            if (! $column->isFreeField() && isset($fields[$column->field_key])) {
                $fieldIndex[$column->field_key] = $i;
            }
        }

        $plan = [];
        foreach ($sheetRows as $n => $row) {
            if (collect($row)->filter(fn ($v) => trim((string) $v) !== '')->isEmpty()) {
                continue;
            }

            $line = $n + 2; // Excel-Zeile (Kopfzeile = 1)
            $key  = trim((string) ($row[$keyIndex] ?? ''));
            $get  = fn (string $field) => isset($fieldIndex[$field]) ? trim((string) ($row[$fieldIndex[$field]] ?? '')) : null;

            $record = $key !== '' ? $this->findRecord($key) : null;
            $entry  = ['line' => $line, 'action' => 'unchanged', 'label' => $this->recordLabel($record, $get), 'changes' => [], 'error' => null, '_ops' => ['id' => $record?->cis_row_id, 'set' => [], 'after' => []]];

            if ($key !== '' && ! $record) {
                $plan[] = [...$entry, 'action' => 'error', 'error' => 'Import-Key gehört zu keinem Datensatz.'];
                continue;
            }
            if ($key === '') {
                if (! $allowCreate || ! $this->canCreate()) {
                    $plan[] = [...$entry, 'action' => 'skipped', 'error' => $this->canCreate()
                        ? 'Kein Import-Key – Anlegen neuer Datensätze ist nicht aktiviert.'
                        : 'Kein Import-Key – neue Datensätze lassen sich hier nicht anlegen.'];
                    continue;
                }
                $missing = collect($this->requiredForCreate())->filter(fn ($f) => ($get($f) ?? '') === '')->map(fn ($f) => $fields[$f]['label'] ?? $f);
                if ($missing->isNotEmpty()) {
                    $plan[] = [...$entry, 'action' => 'error', 'error' => 'Neuer Datensatz braucht: ' . $missing->join(', ') . '.'];
                    continue;
                }
                $entry['action'] = 'create';
            }

            $error = null;
            foreach ($fieldIndex as $field => $index) {
                $spec = $fields[$field];
                if (empty($spec['import'])) {
                    continue;
                }
                $raw = trim((string) ($row[$index] ?? ''));
                if ($raw === '' && empty($spec['clearable'])) {
                    continue;
                }

                try {
                    $result = $this->importField($record, $field, $raw, $get);
                } catch (DatasetImportException $e) {
                    $error = $e->getMessage();
                    break;
                }
                if ($result === null) {
                    continue;
                }

                $entry['changes'][$result['label']] = [$result['old'], $result['new']];
                if (! empty($result['set'])) {
                    $entry['_ops']['set'] = array_merge($entry['_ops']['set'], $result['set']);
                }
                if (isset($result['after'])) {
                    $entry['_ops']['after'][] = $result['after'];
                }
            }

            if ($error) {
                $entry['action'] = 'error';
                $entry['error']  = $error;
            } elseif ($entry['action'] !== 'create') {
                $entry['action'] = empty($entry['changes']) ? 'unchanged' : 'update';
            }
            $plan[] = $entry;
        }

        return $plan;
    }

    /** Schreibt alle fehlerfreien create/update-Einträge des Plans in einer Transaktion. @return array{created: int, updated: int} */
    public function apply(array $plan): array
    {
        $created = $updated = 0;

        DB::transaction(function () use ($plan, &$created, &$updated) {
            foreach ($plan as $entry) {
                if (! in_array($entry['action'], ['create', 'update'], true)) {
                    continue;
                }

                $ops    = $entry['_ops'];
                $record = $ops['id'] ? $this->findRecord($ops['id']) : $this->newRecord();
                if (! $record) {
                    continue;
                }

                $record->forceFill($ops['set'])->save();
                foreach ($ops['after'] as $after) {
                    $after($record);
                }

                $entry['action'] === 'create' ? $created++ : $updated++;
            }
        });

        return ['created' => $created, 'updated' => $updated];
    }

    // ── Hilfen für Unterklassen ─────────────────────────────────────────────

    /** @return string[] */
    protected function splitList(string $raw): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[;\n]+/', $raw)), fn ($v) => $v !== ''));
    }

    protected function parseBool(string $raw, string $label): bool
    {
        return match (mb_strtolower(trim($raw))) {
            'ja', 'yes', 'true', '1', 'x' => true,
            'nein', 'no', 'false', '0'    => false,
            default                       => throw new DatasetImportException("{$label}: „{$raw}“ ist weder Ja noch Nein."),
        };
    }

    protected function parseDecimal(string $raw, string $label): float
    {
        $s = trim(str_replace(['€', ' '], '', $raw));
        if (str_contains($s, ',')) {
            $s = str_replace(['.', ','], ['', '.'], $s);
        }
        if (! is_numeric($s)) {
            throw new DatasetImportException("{$label}: „{$raw}“ ist keine Zahl.");
        }

        return (float) $s;
    }

    protected function parseDate(string $raw, string $label, bool $withTime = false): Carbon
    {
        foreach (['d.m.Y H:i:s', 'd.m.Y H:i', 'd.m.Y', 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d\TH:i', 'Y-m-d'] as $format) {
            try {
                $date = Carbon::createFromFormat('!' . $format, trim($raw));
                if ($date && $date->format($format) === trim($raw)) {
                    return $withTime ? $date : $date->startOfDay();
                }
            } catch (\Throwable) {
            }
        }

        throw new DatasetImportException("{$label}: „{$raw}“ ist kein gültiges Datum (z.B. 31.12.2026" . ($withTime ? ' 14:30' : '') . ').');
    }

    protected function fmtDate(?\DateTimeInterface $date, bool $withTime = false): string
    {
        return $date ? $date->format($withTime ? 'd.m.Y H:i' : 'd.m.Y') : '';
    }

    /** Vergleicht zwei Namenslisten unabhängig von Reihenfolge/Groß-/Kleinschreibung. */
    protected function sameSet(array $a, array $b): bool
    {
        $norm = fn (array $x) => collect($x)->map(fn ($v) => mb_strtolower(trim((string) $v)))->sort()->values()->all();

        return $norm($a) === $norm($b);
    }
}
