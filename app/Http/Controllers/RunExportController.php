<?php

namespace App\Http\Controllers;

use App\Models\Run;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Ohffs\SimpleSpout\ExcelSheet;

class RunExportController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(Run $run)
    {
        $this->authorize('view', $run);

        $filename = Str::slug($run->name) . '-' . now()->format('d-m-Y') . '.xlsx';
        
        $data = [
            ['Name', 'Email', 'Status', 'Notified At', 'Completed At']
        ];

        foreach ($run->deliveries()->orderBy('position')->get() as $delivery) {
            $data[] = [
                $delivery->name,
                $delivery->email,
                $delivery->status->value,
                $delivery->notified_at?->format('Y-m-d H:i:s'),
                $delivery->completed_at?->format('Y-m-d H:i:s'),
            ];
        }

        $path = (new ExcelSheet)->generate($data);

        return response()->download($path, $filename)->deleteFileAfterSend();
    }
}