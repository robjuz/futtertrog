<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'futtertrog:create-admin
                            {--email}
                            --password
                            --name
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an admit user';

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
        if (!$name = $this->input->getParameterOption('name')) {
            $name = $this->ask('name');
        }

        if (!$email = $this->input->getParameterOption('email')) {
         $email = $this->ask('email');
        }

        if (!$password = $this->input->getParameterOption('password')) {
            $password = $this->secret('password');
        }

        User::create([
           'email' => $email,
            'name' => $name,
            'password' => Hash::make($password),
            'is_admin' => true
        ]);
    }
}
