<?php

namespace App\Actions\Expense;

use App\Models\Expense;

class Show
{
    public function execute(Expense $expense)
    {
        return response()->json($expense);
    }
}
