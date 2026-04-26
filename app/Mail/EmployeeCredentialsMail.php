<?php

namespace App\Mail;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeeCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Employee $employee,
        public string $temporaryPassword,
        public bool $isResend = false,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->isResend
            ? 'Your NU HRIS credentials have been reset'
            : 'Welcome to NU HRIS — your login credentials';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.employee-credentials',
            with: [
                'employee' => $this->employee,
                'temporaryPassword' => $this->temporaryPassword,
                'isResend' => $this->isResend,
                'loginUrl' => rtrim((string) config('app.url'), '/').'/login',
            ],
        );
    }
}
