<?php
namespace App\Console\Commands;
use App\Models\InvoicePosition;
use Illuminate\Support\Facades\Storage;
use Illuminate\Console\Command;

class CreatePositions extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'create:positions';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Create invoice positions from a text file.';

  /**
   * Create a new command instance.
   *
   * @return void
   */
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * Execute the console command.
   *
   * @return int
   */
  public function handle()
  {
    $file = storage_path('app/public/positions.txt');

    $handle = fopen($file, 'r');

    while (($line = fgets($handle)) !== false)
    {
      $data = explode(';', $line);
      $hours = str_replace("\r\n", '', $data[2]);
      $position = new InvoicePosition();
      $position->description = $data[0];
      $position->periode = $data[1];
      $position->rate = 125;
      $position->hours = $hours;
      $position->amount = $hours * 125;
      $position->invoice_id = 319;
      $position->save();
    }

    fclose($handle);
    $this->info('positions created...');
  }
}
