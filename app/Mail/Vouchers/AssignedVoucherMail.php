<?php

namespace App\Mail\Vouchers;


use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

class AssignedVoucherMail extends ImplementationMail
{
    private $fundName;
    private $qrToken;
    private $voucher_amount;
    private $voucher_expire_minus_day;

    /**
     * Create a new message instance.
     *
     * AssignedVoucherMail constructor.
     * @param string $fund_name
     * @param string $qrToken
     * @param int $voucher_amount
     * @param string $voucher_expire_minus_day
     * @param EmailFrom|null $emailFrom
     */
    public function __construct(
        string $fund_name,
        string $qrToken,
        int $voucher_amount,
        string $voucher_expire_minus_day,
        ?EmailFrom $emailFrom
    ) {
        $this->setMailFrom($emailFrom);

        $this->fundName = $fund_name;
        $this->qrToken = $qrToken;
        $this->voucher_amount = $voucher_amount;
        $this->voucher_expire_minus_day = $voucher_expire_minus_day;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject(mail_trans('voucher_assigned.title', [
                'fund_name' => $this->fundName
            ]))
            ->view('emails.vouchers.voucher_assigned', [
                'fund_name' => $this->fundName,
                'qr_token'  => $this->qrToken,
                'voucher_amount' => $this->voucher_amount,
                'voucher_expire_minus_day' => $this->voucher_expire_minus_day,
            ]);
    }
}
