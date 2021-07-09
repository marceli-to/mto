<?php
namespace App\Http\Controllers\Api;
use App\Models\Expense;
use App\Http\Resources\ExpenseCollection;
use App\Http\Requests\ExpenseStoreRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
  /**
   * Models
   */
  
  protected $expense;
  
  /**
   * Constructor
   * 
   * @param Expense $expense
   */

  public function __construct(Expense $expense)
  {
    $this->expense = $expense;
  }

  /**
   * Get all records
   * 
   * @return \Illuminate\Http\Response
   */

  public function get()
  {
    $expenses = $this->expense->orderBy('date', 'DESC')->get();
    return response()->json(['data' => $expenses, 'total' => $expenses->sum('amount')]);
  }

  /**
   * Store a newly created resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  
  public function store(ExpenseStoreRequest $request)
  {   
    $expense = new Expense($request->all());
    $expense->save();
    $this->createExpenseNumber($expense);
    return response()->json(['expenseId' => $expense->id]);
  }

  /**
   * Edit a specified resource.
   *
   * @param Expense $expense
   * @return \Illuminate\Http\Response
   */
  public function edit(Expense $expense)
  {
    return response()->json($this->expense->find($expense->id));
  }

  /**
   * Update the status of the specified resource.
   *
   * @param Expense $expense
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function update(Expense $expense, ExpenseStoreRequest $request)
  {
    $expense->update($request->all());
    $expense->save();
    return response()->json('successfully updated');
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  Expense $expense
   * @return \Illuminate\Http\Response
   */
  public function destroy(Expense $expense)
  {
    $expense->delete();
    return response()->json('successfully deleted');
  }

  /**
   * Create the invoice number
   * @param  Expense $expense
   */
  protected function createExpenseNumber(Expense $expense)
  {
    $expense->number = date('y', time()) . '.' . str_pad($expense->id, 4, "0", STR_PAD_LEFT);
    $expense->save();
  }

}
