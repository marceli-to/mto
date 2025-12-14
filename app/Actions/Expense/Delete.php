<?php

namespace App\Actions\Expense;

use App\Models\Expense;

class Delete
{
    public function execute(Expense $expense)
    {
        $expense->delete();

        return response()->json('successfully deleted');
    }
}
