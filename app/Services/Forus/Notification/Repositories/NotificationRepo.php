<?php

namespace App\Services\Forus\Notification\Repositories;

use App\Mail\Auth\UserLoginMail;
use App\Mail\Funds\FundBalanceWarningMail;
use App\Mail\Funds\FundCreatedMail;
use App\Mail\Funds\FundExpiredMail;
use App\Mail\Funds\FundStartedMail;
use App\Mail\Funds\NewFundApplicableMail;
use App\Mail\Funds\ProductAddedMail;
use App\Mail\Funds\ProviderAppliedMail;
use App\Mail\Funds\ProviderApprovedMail;
use App\Mail\Funds\ProviderRejectedMail;
use App\Mail\User\EmailActivationMail;
use App\Mail\Validations\AddedAsValidatorMail;
use App\Mail\Validations\NewValidationRequestMail;
use App\Mail\Vouchers\PaymentSuccessMail;
use App\Mail\Vouchers\ProductReservedMail;
use App\Mail\Vouchers\ProductSoldOutMail;
use App\Mail\Vouchers\SendVoucherMail;
use App\Mail\Vouchers\ShareProductVoucherMail;
use App\Models\NotificationPreference;
use App\Models\NotificationUnsubscription;
use App\Models\NotificationUnsubscriptionToken;
use App\Services\Forus\Notification\Interfaces\INotificationRepo;
use Illuminate\Support\Collection;

/**
 * Class NotificationServiceRepo
 * @package App\Services\Forus\Notification\Repositories
 */
class NotificationRepo implements INotificationRepo
{
    protected $preferencesModel;
    protected $unsubscriptionModel;
    protected $unsubTokenModel;

    /**
     * Map between type keys and Mail classes
     * @var array
     */
    protected static $mailMap = [
        // User generated emails
        'vouchers.send_voucher' => SendVoucherMail::class,
        'vouchers.share_voucher' => ShareProductVoucherMail::class,
        'vouchers.payment_success' => PaymentSuccessMail::class,

        // Validators
        'validations.new_validation_request' => NewValidationRequestMail::class,
        'validations.you_added_as_validator' => AddedAsValidatorMail::class,

        // Mails for sponsors/providers
        'funds.new_fund_started' => FundStartedMail::class,
        'funds.new_fund_created' => FundCreatedMail::class,
        'funds.new_fund_applicable' => NewFundApplicableMail::class,
        'funds.provider_applied' => ProviderAppliedMail::class,
        'funds.provider_approved' => ProviderApprovedMail::class,
        'funds.provider_rejected' => ProviderRejectedMail::class,
        'funds.product_sold_out' => ProductSoldOutMail::class,
        'funds.product_reserved' => ProductReservedMail::class,
        'funds.fund_expires' => FundExpiredMail::class,
        'funds.product_added' => ProductAddedMail::class,
        'funds.balance_warning' => FundBalanceWarningMail::class,

        // Authorization emails
        'auth.user_login' => UserLoginMail::class,
        'auth.email_activation' => EmailActivationMail::class,
    ];

    /**
     * Map between type keys and Mail classes
     * @var array
     */
    protected static $pushNotificationMap = [
        'voucher.assigned',
        'voucher.transaction',
        'employee.created',
        'employee.deleted',
        'bunq.transaction_success',
        'funds.provider_approved',
    ];

    /**
     * Emails that you can't unsubscribe from
     * @var array
     */
    protected static $mandatoryEmail = [
        'auth.user_login', 'auth.email_activation', 'vouchers.share_voucher', 'vouchers.send_voucher',
    ];

    /**
     * @return array
     */
    public static function getMailMap(): array
    {
        return self::$mailMap;
    }

    /**
     * @return array
     */
    public static function getPushNotificationMap(): array
    {
        return self::$pushNotificationMap;
    }

    /**
     * @return array
     */
    public static function getMandatoryMailKeys(): array
    {
        return self::$mandatoryEmail;
    }

    /**
     * NotificationServiceRepo constructor.
     * @param NotificationPreference $preferencesModel
     * @param NotificationUnsubscription $unsubscriptionModel
     * @param NotificationUnsubscriptionToken $unsubscriptionTokenModel
     */
    public function __construct(
        NotificationPreference $preferencesModel,
        NotificationUnsubscription $unsubscriptionModel,
        NotificationUnsubscriptionToken $unsubscriptionTokenModel
    ) {
        $this->preferencesModel = $preferencesModel;
        $this->unsubscriptionModel = $unsubscriptionModel;
        $this->unsubTokenModel = $unsubscriptionTokenModel;
    }

    /**
     * Is email unsubscribed from all emails
     * @param string $email
     * @return bool
     */
    public function isEmailUnsubscribed(string $email): bool {
        return $this->unsubscriptionModel->newQuery()->where(
            compact('email'))->count() > 0;
    }

    /**
     * Check if Mail class can be unsubscribed
     * @param string $emailClass
     * @return bool
     */
    public function isMailUnsubscribable(string $emailClass): bool {
        $keys = array_flip(self::getMailMap());

        if (!isset($keys[$emailClass])) {
            return false;
        }

        return !in_array($keys[$emailClass], self::getMandatoryMailKeys());
    }

    /**
     * Check if Push notification can be unsubscribed
     * @param string $key
     * @return bool
     */
    public function isPushNotificationUnsubscribable(string $key): bool {
        return in_array($key, self::getPushNotificationMap());
    }

    /**
     * Check if Push notification can be unsubscribed
     * @param string $identity_address
     * @param string $key
     * @return bool
     */
    public function isPushNotificationUnsubscribed(
        string $identity_address,
        string $key
    ): bool {
        $subscribed = false;
        $type = 'push';

        return $this->preferencesModel->newQuery()->where(compact(
            'identity_address', 'key', 'subscribed', 'type'
        ))->count() > 0;
    }

    /**
     * Is email $emailClass unsubscribed
     * @param string $identity_address
     * @param string $emailClass
     * @return bool
     * @throws \Exception
     */
    public function isEmailTypeUnsubscribed(
        $identity_address,
        $emailClass
    ): bool {
        if (!$this->isMailUnsubscribable($emailClass)) {
            return false;
        }

        $key = array_flip(self::getMailMap())[$emailClass];
        $type = 'email';
        $subscribed = false;

        return $this->preferencesModel->newQuery()->where(compact(
            'identity_address', 'key', 'subscribed', 'type'
            ))->count() > 0;
    }

    /**
     * Create new unsubscription from all emails link
     * @param string $email
     * @param string|null $token
     * @return string
     */
    public function makeUnsubLink(string $email, string $token = null): string {
        return url(sprintf(
            '/notifications/unsubscribe/%s',
            $this->makeToken($email, $token)
        ));
    }

    /**
     * Create new unsubscription from all emails link
     * @param string $email
     * @param string|null $token
     * @return string
     */
    public function makeReSubLink(string $email, string $token = null): string {
        return url(sprintf(
            '/notifications/subscribe/%s',
            $this->makeToken($email, $token)
        ));
    }

    /**
     * Try to reuse existing token or create new one
     * @param string $email
     * @param string|null $token
     * @return string
     */
    private function makeToken(string $email, string $token = null) {
        $model = $token ? $this->unsubTokenModel->findByToken($token) : null;

        return ($model ?: $this->unsubTokenModel->makeToken($email))->token;
    }

    /**
     * Unsubscribe email from all notifications
     * @param string $email
     */
    public function unsubscribeEmail(
        string $email
    ): void {
        $this->unsubscriptionModel->newQuery()->firstOrCreate(
            compact('email')
        );
    }

    /**
     * Remove email unsubscription from all notifications
     * @param string $email
     */
    public function reSubscribeEmail(
        string $email
    ): void {
        $this->unsubscriptionModel->where(compact('email'))->delete();
    }

    /**
     * @param string $token
     * @param bool $active
     * @return string|null
     */
    public function emailByUnsubscribeToken(
        string $token,
        bool $active = true
    ): ?string {
        $token = $this->unsubTokenModel->findByToken($token, $active);

        return $token ? $token->email : null;
    }

    /**
     * @param string $identityAddress
     * @return Collection
     */
    public function getNotificationPreferences(
        string $identityAddress
    ): Collection {
        $subscribed = false;
        $identity_address = $identityAddress;

        $mailKeys = collect();
        foreach (self::mailTypeKeys() as $mailKey) {
            $mailKeys->push((object)[
                'value' => $mailKey,
                'type'  => 'email'
            ]);
        }

        $pushKeys = collect();
        foreach (self::getPushNotificationMap() as $pushKey) {
            $pushKeys->push((object)[
                'value' => $pushKey,
                'type'  => 'push'
            ]);
        }

        $keys = $mailKeys->merge($pushKeys);

        $unsubscribedKeys = $this->preferencesModel->where(compact(
            'identity_address', 'subscribed'
        ))->pluck('key')->values();

        return $keys->map(function($key) use ($unsubscribedKeys) {
            return [
                'key'  => $key->value,
                'type' => $key->type,
                'subscribed' => $unsubscribedKeys->search($key->value) === false
            ];
        })->values();
    }

    /**
     * @param string $identityAddress
     * @param array $data
     * @return Collection
     */
    public function updateIdentityPreferences(
        string $identityAddress,
        array $data
    ): Collection {
        $data_keys = array_keys(array_pluck($data, 'subscribed', 'key'));
        $preference_keys = self::allPreferenceKeys();

        foreach ($data as $setting) {
            if (array_intersect($preference_keys, $data_keys)) {
                $this->preferencesModel->newQuery()->firstOrCreate([
                    'identity_address'  => $identityAddress,
                    'key'               => $setting['key'],
                    'type'              => $setting['type'],
                ])->update([
                    'subscribed'        => $setting['subscribed']
                ]);
            }
        }

        return $this->getNotificationPreferences($identityAddress);
    }

    /**
     * @return array
     */
    public function mailTypeKeys(): array
    {
        return array_values(array_diff(
            array_keys(self::getMailMap()),
            self::getMandatoryMailKeys()
        ));
    }

    /**
     * @return array
     */
    public function pushNotificationTypeKeys() : array {
        return self::getPushNotificationMap();
    }

    /**
     * @return array
     */
    public function allPreferenceKeys(): array {
        return array_merge($this->mailTypeKeys(), $this->pushNotificationTypeKeys());
    }
}