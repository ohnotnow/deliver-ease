<?php

namespace App\Livewire\Runs;

use App\Models\Run;
use Flux\Flux;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithFileUploads;
use Ohffs\SimpleSpout\ExcelSheet;

class Import extends Component
{
    use WithFileUploads;

    public Run $run;

    public $file;

    public array $previewRows = [];

    public function mount(Run $run): void
    {
        $this->run = $run;
        $this->authorize('update', $run);
    }

    public function removeFile()
    {
        $this->reset('file', 'previewRows');
    }

    public function updatedFile()
    {
        $this->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB max
        ]);

        $this->previewRows = [];

        $path = $this->file->getRealPath();

        try {
            // Import the first sheet
            $rows = (new ExcelSheet)->importFirst($path);

            foreach ($rows as $index => $row) {
                // Expecting simple format: Col A = email, Col B = name (optional)
                $email = isset($row[0]) ? strtolower(trim($row[0])) : null;
                $name = isset($row[1]) ? trim($row[1]) : null;

                // Skip completely empty rows
                if (empty($email) && empty($name)) {
                    continue;
                }

                $validator = Validator::make(
                    ['email' => $email, 'name' => $name],
                    [
                        'email' => 'required|email',
                        'name' => 'nullable|string|max:255',
                    ]
                );

                // If it's the very first row and it fails validation, assume it's a header
                if ($index === 0 && $validator->fails()) {
                    continue;
                }

                $this->previewRows[] = [
                    'email' => $email,
                    'name' => $name,
                    'isValid' => $validator->passes(),
                    'errors' => $validator->errors()->all(),
                ];
            }

        } catch (\Exception $e) {
            Flux::toast('Error reading file: '.$e->getMessage(), variant: 'danger');
        }
    }

    public function import()
    {
        $validRows = array_filter($this->previewRows, fn ($row) => $row['isValid']);

        if (empty($validRows)) {
            Flux::toast('No valid rows to import.', variant: 'warning');

            return;
        }

        // Get the current max position to append new items
        $currentMaxPosition = $this->run->deliveries()->max('position') ?? 0;
        $importedCount = 0;
        $updatedCount = 0;

        foreach ($validRows as $row) {
            $email = strtolower($row['email']);

            $delivery = $this->run->deliveries()->where('email', $email)->first();

            if ($delivery) {
                $delivery->update([
                    'name' => $row['name'],
                ]);
                $updatedCount++;
            } else {
                $currentMaxPosition++;
                $this->run->deliveries()->create([
                    'email' => $email,
                    'name' => $row['name'],
                    'position' => $currentMaxPosition,
                ]);
                $importedCount++;
            }
        }

        Flux::toast("Import complete: {$importedCount} created, {$updatedCount} updated.");

        return $this->redirect(route('runs.edit', $this->run), navigate: true);
    }

    public function render()
    {
        return view('livewire.runs.import');
    }
}