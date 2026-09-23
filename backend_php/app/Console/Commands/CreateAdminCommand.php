<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create
                            {--email= : Email address of the admin}
                            {--password= : Password for the admin}
                            {--name= : Name of the admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or update an administrator account from environment variables or options';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->option('email') ?: env('ADMIN_EMAIL');
        $password = $this->option('password') ?: env('ADMIN_PASSWORD');
        $name = $this->option('name') ?: env('ADMIN_NAME', 'Administrator');

        // ถ้าไม่มีค่าใน env หรือ option ให้ถามผู้ใช้แบบ interactive (เฉพาะเมื่อรันแบบมี TTY)
        if (empty($email) && $this->input->isInteractive()) {
            $email = $this->ask('Enter admin email address:');
        }

        if (empty($password) && $this->input->isInteractive()) {
            $password = $this->secret('Enter admin password:');
        }

        if (empty($email) || empty($password)) {
            $this->error('Missing admin credentials. Please provide --email and --password, or set ADMIN_EMAIL and ADMIN_PASSWORD in environment.');
            return Command::FAILURE;
        }

        $email = trim(strtolower($email));

        // ตรวจสอบความถูกต้องของอีเมลและรหัสผ่าน
        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            [
                'email' => ['required', 'email'],
                'password' => ['required', 'string', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return Command::FAILURE;
        }

        // ป้องกันการใช้ password123 ใน production
        if (app()->environment('production') && $password === 'password123') {
            $this->error('The password "password123" is strictly prohibited in production environment. Please choose a strong password.');
            return Command::FAILURE;
        }

        // ค้นหาผู้ใช้เดิม หรือสร้างใหม่
        $user = User::where('email', $email)->first();

        if ($user) {
            $user->name = $name;
            $user->password = Hash::make($password);
            $user->role = 'admin';
            $user->save();
            $this->info("Administrator [{$email}] updated successfully.");
        } else {
            User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'admin',
            ]);
            $this->info("Administrator [{$email}] created successfully.");
        }

        return Command::SUCCESS;
    }
}
