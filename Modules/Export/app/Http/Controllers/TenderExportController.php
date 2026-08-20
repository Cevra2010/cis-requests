<?php

namespace Modules\Export\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Modules\Export\Models\ExportTemplate;
use Modules\Export\Services\ExportFileBuilder;
use Modules\Export\Services\TenderExporter;

class TenderExportController extends Controller
{
    public function download(
        string $project,
        string $template,
        string $format,
        TenderExporter $exporter,
        ExportFileBuilder $builder
    ) {
        abort_unless(in_array($format, ['csv', 'xlsx'], true), 404);

        $project  = Project::where('cis_row_id', $project)->firstOrFail();
        $template = ExportTemplate::with('columns')->where('cis_row_id', $template)->firstOrFail();

        abort_if($template->columns->isEmpty(), 422, 'Diese Vorlage hat noch keine Spalten.');

        $data    = $exporter->build($project, $template);
        $content = $builder->build($data['headers'], $data['rows'], $format, $template->name);

        $filename = str($project->name . '-' . $template->name)->slug() . '.' . $format;
        $mime = $format === 'xlsx'
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'text/csv; charset=UTF-8';

        return response($content, 200, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
