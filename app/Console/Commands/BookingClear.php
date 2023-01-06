<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;

class BookingClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booking:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Liberar reservas no finalizadas';

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

        Booking::update_temporary_book();

        //return 0;
    }
}
