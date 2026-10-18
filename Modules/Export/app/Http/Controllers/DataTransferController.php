<?php

namespace Modules\Export\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\DocumentNaming;
use Modules\Export\Models\ExportTemplate;
use Modules\Export\Services\Datasets\Datasets;
use Modules\Export\Services\ExportFileBuilder;

class DataTransferController extends Controller
{
    public function download(string $template, string $format, ExportFileBuilder $builder)
    {
        abort_unless(auth()->user()?->hasPermission('export.transfer'), 403);
        abort_unless(in_array($format, ['csv', 'xlsx'], true), 404);

        $template = ExportTemplate::with('columns')->where('cis_row_id', $template)->firstOrFail();
        abort_unless($template->hasUsage('data_transfer'), 404);
        abort_if($template->columns->isEmpty(), 422, 'Diese Vorlage hat noch keine Spalten.');

        $dataset = Datasets::find($template->dataset);
        abort_unless($dataset && $dataset->available(), 404);

        $data = $dataset->build($template);

        $content  = $builder->build($data['headers'], $data['rows'], $format, $template->name, $data['import_key_index']);
        $filename = DocumentNaming::downloadFilename($template->name, $format);
        $mime     = $format === 'xlsx'
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'text/csv; charset=UTF-8';

        return response($content, 200, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
