<?php

namespace App\Mail\Digest;

class DigestRequesterMail extends BaseDigestMail
{
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        return $this->view('emails.mail-digest')->subject('Update: Nieuw aanbod op de webshop');
    }
}
