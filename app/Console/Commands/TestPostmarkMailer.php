<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestPostmarkMailer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-postmark-mailer {email? : The email address to send the test to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Postmark Symfony mailer integration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?: config('mail.from.address');
        
        $this->info("Sending test email to: {$email}");
        
        try {
            Mail::raw('This is a test email from the PBonchev application using Symfony Postmark mailer.', function ($message) use ($email) {
                $message->to($email)
                    ->subject('Test Email from PBonchev App');
            });
            
            $this->info('Test email sent successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to send test email: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}
