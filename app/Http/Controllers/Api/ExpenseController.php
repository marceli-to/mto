<?php

namespace App\Http\Controllers\Api;

use App\Models\Expense;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseStoreRequest;
use App\Actions\Expense\Get as GetAction;
use App\Actions\Expense\Show as ShowAction;
use App\Actions\Expense\Store as StoreAction;
use App\Actions\Expense\Update as UpdateAction;
use App\Actions\Expense\Delete as DeleteAction;

class ExpenseController extends Controller
{
    public function get()
    {
        return (new GetAction)->execute();
    }

    public function store(ExpenseStoreRequest $request)
    {
        return (new StoreAction)->execute($request);
    }

    public function edit(Expense $expense)
    {
        return (new ShowAction)->execute($expense);
    }

    public function update(Expense $expense, ExpenseStoreRequest $request)
    {
        return (new UpdateAction)->execute($expense, $request);
    }

    public function destroy(Expense $expense)
    {
        return (new DeleteAction)->execute($expense);
    }
}
