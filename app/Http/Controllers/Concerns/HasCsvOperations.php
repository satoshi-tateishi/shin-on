<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

trait HasCsvOperations
{
    /**
     * CSV エクスポート
     */
    public function exportCsv(): Response
    {
        $modelClass = $this->getModelClass();
        $data = $modelClass::query()
            ->when(method_exists($modelClass, 'scopeOrdered'), function ($query) {
                $query->ordered();
            })
            ->get();

        $csvData = $this->prepareCsvExportData($data);
        $filename = $this->getCsvFilename('export');

        return response($csvData)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Content-Transfer-Encoding', 'binary');
    }

    /**
     * CSV テンプレート ダウンロード
     */
    public function templateCsv(): Response
    {
        $headers = $this->getCsvHeaders();
        $filename = $this->getCsvFilename('template');

        $csvData = "\xEF\xBB\xBF".implode(',', $headers)."\n";

        return response($csvData)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Content-Transfer-Encoding', 'binary');
    }

    /**
     * CSV インポート
     */
    public function importCsv(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ], [
            'csv_file.required' => 'CSVファイルを選択してください。',
            'csv_file.mimes' => 'CSVファイルをアップロードしてください。',
            'csv_file.max' => 'ファイルサイズは2MB以内にしてください。',
        ]);

        try {
            $csvContent = file_get_contents($request->file('csv_file')->getPathname());
            $result = $this->processCsvImport($csvContent);

            if ($result['success']) {
                return redirect()->back()->with('success',
                    "CSVインポートが完了しました。{$result['imported']}件のデータを処理しました。");
            } else {
                return redirect()->back()->with('error',
                    'CSVインポートでエラーが発生しました: '.implode(', ', $result['errors']));
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'CSVファイルの処理中にエラーが発生しました: '.$e->getMessage());
        }
    }

    /**
     * CSV エクスポート データ準備
     */
    protected function prepareCsvExportData($data): string
    {
        $headers = $this->getCsvHeaders();
        $csvData = "\xEF\xBB\xBF".implode(',', $headers)."\n";

        foreach ($data as $record) {
            $row = $this->mapRecordToCsvRow($record);
            $csvData .= implode(',', array_map(function ($value) {
                return '"'.str_replace('"', '""', $value ?? '').'"';
            }, $row))."\n";
        }

        return $csvData;
    }

    /**
     * CSV インポート処理
     */
    protected function processCsvImport(string $csvContent): array
    {
        // UTF-8 BOM を削除
        $csvContent = str_replace("\xEF\xBB\xBF", '', $csvContent);

        $lines = explode("\n", $csvContent);
        $headers = str_getcsv(array_shift($lines));

        $imported = 0;
        $errors = [];
        $modelClass = $this->getModelClass();

        foreach ($lines as $lineNumber => $line) {
            if (empty(trim($line))) {
                continue;
            }

            try {
                $data = str_getcsv($line);
                $recordData = $this->mapCsvRowToRecord($headers, $data);

                if ($this->validateCsvRecord($recordData, $lineNumber + 2)) {
                    $modelClass::updateOrCreate(
                        $this->getUniqueIdentifier($recordData),
                        $recordData
                    );
                    $imported++;
                }
            } catch (\Exception $e) {
                $errors[] = '行'.($lineNumber + 2).': '.$e->getMessage();
            }
        }

        return [
            'success' => empty($errors),
            'imported' => $imported,
            'errors' => $errors,
        ];
    }

    /**
     * 各コントローラで実装すべき抽象メソッド
     */
    abstract protected function getModelClass(): string;

    abstract protected function getCsvHeaders(): array;

    abstract protected function mapRecordToCsvRow($record): array;

    abstract protected function mapCsvRowToRecord(array $headers, array $data): array;

    abstract protected function getUniqueIdentifier(array $recordData): array;

    abstract protected function getCsvFilename(string $type): string;

    /**
     * CSV レコード バリデーション（オプション）
     */
    protected function validateCsvRecord(array $recordData, int $lineNumber): bool
    {
        return true;
    }
}
