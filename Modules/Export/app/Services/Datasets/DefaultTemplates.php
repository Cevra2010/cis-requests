<?php

namespace Modules\Export\Services\Datasets;

use App\Models\Setting;
use Modules\Export\Models\ExportTemplate;
use Modules\Export\Models\ExportTemplateColumn;

/**
 * Legt je verfügbarem Systemdatensatz einmalig eine Vorlage "<Name> – alle Felder" an, damit das Import-/Export-Werkzeug
 * sofort nutzbar ist. Welche Datensätze bereits versorgt wurden, steht in der Einstellung "export_default_templates" –
 * eine absichtlich gelöschte Vorlage kommt dadurch nicht zurück.
 */
class DefaultTemplates
{
    public static function ensure(): void
    {
        $done = json_decode((string) Setting::get('export_default_templates', '[]'), true) ?: [];

        foreach (Datasets::available() as $key => $dataset) {
            if (in_array($key, $done, true)) {
                continue;
            }

            // Nach einem Reset der Einstellungen ist die Merkliste leer, die Vorlage aber evtl. noch da – dann nicht doppelt anlegen.
            if (ExportTemplate::where('dataset', $key)->where('name', $dataset->label() . ' – alle Felder')->exists()) {
                $done[] = $key;
                Setting::set('export_default_templates', json_encode($done));
                continue;
            }

            $template = ExportTemplate::create([
                'name'               => $dataset->label() . ' – alle Felder',
                'phase'              => 'pre_tender',
                'dataset'            => $key,
                'include_import_key' => $dataset->supportsImport(),
                'usages'             => ['data_transfer'],
            ]);

            $fields = $dataset->fields();
            foreach (array_values($dataset->defaultColumns()) as $i => $field) {
                ExportTemplateColumn::create([
                    'cis_row_id_template' => $template->cis_row_id,
                    'label'               => $fields[$field]['label'],
                    'field_key'           => $field,
                    'sort_order'          => $i + 1,
                ]);
            }

            $done[] = $key;
            Setting::set('export_default_templates', json_encode($done));
        }
    }
}
