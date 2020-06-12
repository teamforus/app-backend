<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

/**
 * Class ProviderAppliedMail
 * @package App\Mail\Funds
 */
class ProviderAppliedMail extends ImplementationMail
{

    private $provider_name;
    private $sponsor_name;
    private $fund_name;
    private $sponsor_dashboard_link;

    public function __construct(
        string $provider_name,
        string $sponsor_name,
        string $fund_name,
        string $sponsor_dashboard_link,
        ?EmailFrom $emailFrom
    ) {
        $this->setMailFrom($emailFrom);
        $this->provider_name            = $provider_name;
        $this->sponsor_name             = $sponsor_name;
        $this->fund_name                = $fund_name;
        $this->sponsor_dashboard_link   = $sponsor_dashboard_link;
    }

    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject(mail_trans('provider_applied.title', [
                'provider_name' => $this->provider_name,
                'fund_name' => $this->fund_name
            ]))
            ->view('emails.funds.provider_applied', [
                'provider_name'             => $this->provider_name,
                'sponsor_name'              => $this->sponsor_name,
                'fund_name'                 => $this->fund_name,
                'sponsor_dashboard_link'    => $this->sponsor_dashboard_link
            ]);
    }
}
