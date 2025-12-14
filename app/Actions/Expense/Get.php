<?php

namespace App\Actions\Expense;

use App\Models\Expense;

class Get
{
    public function execute()
    {
        $expenses = Expense::orderBy('date', 'DESC')->get();

        return response()->json([
            'data' => $expenses,
            'total' => $expenses->sum('amount')
        ]);
    }
}
