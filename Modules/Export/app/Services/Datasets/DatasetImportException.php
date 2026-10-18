<?php

namespace Modules\Export\Services\Datasets;

/** Ein Wert einer Importzeile ist ungültig – die Zeile wird im Prüfplan als Fehler gemeldet und nicht übernommen. */
class DatasetImportException extends \RuntimeException
{
}
