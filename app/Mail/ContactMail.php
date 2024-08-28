<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public $details;
    public $subject;


    public function __construct($details, $subject)
    {
        $this->details = $details;
        $this->subject = $subject;

    }

    public function build()
    {
        return $this->subject($this->subject)->view('emails.contact');
    }
}
