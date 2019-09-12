<?php

namespace App\Mail\Funds\Forus;

use App\Mail\ImplementationMail;

/**
 * Class FundCreatedMail
 * @package App\Mail\Funds\Forus
 */
class FundCreatedMail extends ImplementationMail
{
    private $fundName;
    private $organizationName;

    public function __construct(
        string $email,
        string $fundName,
        string $organizationName
    ) {
        parent::__construct($email, null);

        $this->fundName = $fundName;
        $this->organizationName = $organizationName;
    }

    public function build(): ImplementationMail
    {
        return parent::build()
            ->subject(mail_trans('fund_created.title', [
                'fund_name' => $this->fundName
            ]))
            ->view('emails.forus.new_fund_created', [
                'fund_name' => $this->fundName,
                'organization_name' => $this->organizationName
            ]);
    }
}
