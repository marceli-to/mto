<?php

namespace App\Actions\Expense;

use App\Models\Expense;
use App\Http\Requests\ExpenseStoreRequest;

class Update
{
    public function execute(Expense $expense, ExpenseStoreRequest $request)
    {
        $expense->update($request->all());
        $expense->save();

        return response()->json('successfully updated');
    }
}
