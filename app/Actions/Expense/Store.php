<?php

namespace App\Actions\Expense;

use App\Models\Expense;
use App\Http\Requests\ExpenseStoreRequest;
use Illuminate\Support\Facades\Storage;

class Store
{
    public function execute(ExpenseStoreRequest $request)
    {
        $expense = new Expense($request->all());
        $expense->save();

        $expense->generateNumber();
        $this->moveUploadedFile($expense, $request->input('temp_file'));

        return response()->json($expense);
    }

    protected function moveUploadedFile(Expense $expense, ?string $tempFile): void
    {
        if (!$tempFile) {
            return;
        }

        $tempPath = 'public/temp/' . $tempFile;
        $finalPath = 'public/media/expenses/' . $expense->number . '.jpg';

        if (Storage::exists($tempPath)) {
            Storage::move($tempPath, $finalPath);
        }
    }
}
