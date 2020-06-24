<?php


namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

/**
 * Class VoucherMail
 * @package App\Mail\Vouchers
 */
class SendVoucherMail extends ImplementationMail
{
    private $fundName;
    private $fund_product_name;
    private $qrToken;
    private $voucher_amount;
    private $voucher_expire_minus_day;

    public function __construct(
        string $fund_name,
        string $fund_product_name,
        string $qrToken,
        int $voucher_amount,
        string $voucher_expire_minus_day,
        ?EmailFrom $emailFrom
    ) {
        $this->setMailFrom($emailFrom);

        $this->fundName = $fund_name;
        $this->fund_product_name = $fund_product_name;
        $this->qrToken = $qrToken;
        $this->voucher_amount = $voucher_amount;
        $this->voucher_expire_minus_day = $voucher_expire_minus_day;
    }

    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject(mail_trans('voucher_sent.title', [
                'fund_name' => $this->fundName
            ]))
            ->view('emails.vouchers.voucher_sent', [
                'fund_name' => $this->fundName,
                'fund_product_name' => $this->fund_product_name,
                'qr_token' => $this->qrToken,
                'voucher_amount' => $this->voucher_amount,
                'voucher_expire_minus_day' => $this->voucher_expire_minus_day,
            ]);
    }
}