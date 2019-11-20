<?php
namespace App\Http\Controllers\Api;
use App\Models\Time;
use App\Http\Resources\TimeCollection;
use App\Http\Requests\TimeStoreRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TimerController extends Controller
{
    protected $time;
    
    /**
     * Constructor
     * 
     * @param Time $time
     */

    public function __construct(Time $time)
    {
        $this->time = $time;
    }

    /**
     * Get all records
     *
     * @return \Illuminate\Http\Response
     */

    public function get()
    {
        $times = $this->time->with('project.client')
                            ->orderBy('date', 'DESC')
                            ->orderBy('timeStart', 'DESC')
                            ->get();
        return new TimeCollection($times);
    }

    /**
     * Get all records
     *
     * @return \Illuminate\Http\Response
     */

    public function getByDay()
    {
        $times = $this->time->with('project.client')
                            ->orderBy('date', 'DESC')
                            ->orderBy('timeStart', 'DESC')
                            ->get();

        $grouped = $times->groupBy('date');
        $timeItems = [];
        foreach($grouped as $key => $group)
        {
            $timeItems[] = [
                'date'      => $key,
                'total'     => $group->sum('minutes')/60,
                'entries'   => $group
            ];
        }

        return response()->json($timeItems);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    
    public function store(TimeStoreRequest $request)
    {   
        $time = new Time($request->all());
        $time->save();
        return response()->json(['id' => $time->id]);
    }

    /**
     * Edit a specified resource.
     *
     * @param Time $time
     * @return \Illuminate\Http\Response
     */
    public function edit(Time $time)
    {
        return response()->json($time);
    }

    /**
     * Update the status of the specified resource.
     *
     * @param Time $time
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Time $time, TimeStoreRequest $request)
    {
        $time->update($request->all());
        return response()->json('successfully updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Time $time
     * @return \Illuminate\Http\Response
     */
    public function destroy(Time $time)
    {
        $time->delete();
        return response()->json('successfully deleted');
    }
}
