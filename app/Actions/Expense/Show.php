<?php

namespace App\Actions\Expense;

use App\Models\Expense;
use Illuminate\Support\Facades\Storage;

class Show
{
    public function execute(Expense $expense)
    {
        $data = $expense->toArray();
        $filePath = 'public/media/expenses/' . $expense->number . '.jpg';
        $data['has_file'] = Storage::exists($filePath);
        $data['file_name'] = $data['has_file'] ? $expense->number . '.jpg' : null;

        return response()->json($data);
    }
}
