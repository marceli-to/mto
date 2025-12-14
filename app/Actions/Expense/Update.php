<?php

namespace App\Actions\Expense;

use App\Models\Expense;
use App\Http\Requests\ExpenseStoreRequest;
use Illuminate\Support\Facades\Storage;

class Update
{
    public function execute(Expense $expense, ExpenseStoreRequest $request)
    {
        $expense->update($request->all());
        $expense->save();

        if ($request->input('delete_file')) {
            $this->deleteExistingFile($expense);
        }

        $this->moveUploadedFile($expense, $request->input('temp_file'));

        return response()->json($expense);
    }

    protected function deleteExistingFile(Expense $expense): void
    {
        $filePath = 'public/media/expenses/' . $expense->number . '.jpg';
        if (Storage::exists($filePath)) {
            Storage::delete($filePath);
        }
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
