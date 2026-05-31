<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * ResetPasswordMail — email yang dikirim ke user saat request reset password.
 *
 * Berisi link unik ke halaman reset di frontend, formatnya:
 *   FRONTEND_URL/reset-password?token=<token>&email=<email>
 *
 * Token expired setelah 60 menit (config di AuthController).
 */
class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param string $userName    Nama lengkap user (untuk greeting)
     * @param string $resetUrl    URL lengkap dengan token & email
     * @param int    $expiryMins  Berapa menit lagi link expired
     */
    public function __construct(
        public string $userName,
        public string $resetUrl,
        public int    $expiryMins = 60,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Password - IMA Creative Production',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reset-password',
            with: [
                'userName'   => $this->userName,
                'resetUrl'   => $this->resetUrl,
                'expiryMins' => $this->expiryMins,
            ],
        );
    }
}
