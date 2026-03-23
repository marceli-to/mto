<?php

namespace App\Actions\Expense;

use App\Models\Expense;
use Illuminate\Support\Facades\Storage;

class Show
{
    public function execute(Expense $expense)
    {
        $data = $expense->toArray();
        $data['has_file'] = false;
        $data['file_name'] = null;

        foreach (['jpg', 'pdf'] as $ext) {
            $filePath = 'public/media/expenses/' . $expense->number . '.' . $ext;
            if (Storage::exists($filePath)) {
                $data['has_file'] = true;
                $data['file_name'] = $expense->number . '.' . $ext;
                break;
            }
        }

        return response()->json($data);
    }
}
