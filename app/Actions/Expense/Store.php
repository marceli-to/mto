<?php

namespace App\Actions\Expense;

use App\Models\Expense;
use App\Http\Requests\ExpenseStoreRequest;

class Store
{
    public function execute(ExpenseStoreRequest $request)
    {
        $expense = new Expense($request->all());
        $expense->save();

        $this->createExpenseNumber($expense);

        return response()->json(['expenseId' => $expense->id]);
    }

    protected function createExpenseNumber(Expense $expense): void
    {
        $expense->number = date('y', time()) . '.' . str_pad($expense->id, 4, "0", STR_PAD_LEFT);
        $expense->save();
    }
}
