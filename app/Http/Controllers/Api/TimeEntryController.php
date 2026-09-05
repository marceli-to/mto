<?php

namespace App\Http\Controllers\Api;

use App\Models\TimeEntry;
use App\Http\Controllers\Controller;
use App\Http\Requests\TimeEntryStoreRequest;
use App\Actions\TimeEntry\Get as GetAction;
use App\Actions\TimeEntry\Show as ShowAction;
use App\Actions\TimeEntry\Store as StoreAction;
use App\Actions\TimeEntry\Update as UpdateAction;
use App\Actions\TimeEntry\Delete as DeleteAction;
use App\Actions\TimeEntry\Bill as BillAction;
use App\Actions\TimeEntry\Unbill as UnbillAction;
use Illuminate\Http\Request;

class TimeEntryController extends Controller
{
    public function get(Request $request)
    {
        return (new GetAction)->execute($request);
    }

    public function config()
    {
        return response()->json([
            'activities' => config('timetracking.activities', []),
        ]);
    }

    public function store(TimeEntryStoreRequest $request)
    {
        return (new StoreAction)->execute($request);
    }

    public function edit(TimeEntry $timeEntry)
    {
        return (new ShowAction)->execute($timeEntry);
    }

    public function update(TimeEntry $timeEntry, TimeEntryStoreRequest $request)
    {
        return (new UpdateAction)->execute($timeEntry, $request);
    }

    public function destroy(TimeEntry $timeEntry)
    {
        return (new DeleteAction)->execute($timeEntry);
    }

    public function bill(Request $request)
    {
        return (new BillAction)->execute($request);
    }

    public function unbill(Request $request)
    {
        return (new UnbillAction)->execute($request);
    }
}
