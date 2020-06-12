<?php


namespace App\Digests;

use App\Mail\Digest\DigestRequesterMail;
use App\Mail\MailBodyBuilder;
use App\Models\Fund;
use App\Models\Voucher;
use App\Models\Implementation;
use App\Services\EventLogService\Models\EventLog;
use App\Services\Forus\Identity\Models\Identity;
use App\Services\Forus\Notification\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Collection;

/**
 * Class RequesterDigest
 * @package App\Digests
 */
class RequesterDigest
{
    use Dispatchable;

    /**
     * @param NotificationService $notificationService
     */
    public function handle(NotificationService $notificationService): void
    {
        $funds = Fund::whereState(Fund::STATE_ACTIVE)->get();

        $fundsProvidersBody = collect($this->buildFundProvidersMailBody($funds));
        $fundsProductsBody = collect($this->buildFundProductsMailBody($funds));

        foreach ($funds as $fund) {
            $this->updateLastDigest($fund);
        }

        foreach ($this->getIdentitiesWithFunds() as $identityFunds) {
            /** @var Identity $identity */
            $identity = $identityFunds['identity'];

            $emailBody = new MailBodyBuilder();
            $emailBody->h1('Update: Nieuwe aanbod op de webshop');

            foreach ($identityFunds['funds'] as $fundId) {
                if (isset($fundsProvidersBody[$fundId])) {
                    $emailBody = $emailBody->merge($fundsProvidersBody[$fundId]);
                }
            }

            if ($emailBody->count() > 1) {
                $emailBody->separator();
            }

            foreach ($identityFunds['funds'] as $fundId) {
                if (isset($fundsProductsBody[$fundId])) {
                    $emailBody = $emailBody->merge($fundsProductsBody[$fundId]);
                }
            }

            if ($emailBody->count() === 1) {
                continue;
            }

            $emailBody->space();

            $emailBody->button_primary(
                Implementation::general_urls()['url_validator'],
                'GA NAAR HET WEBSHOP'
            );

            $notificationService->sendDigest(
                $identity->email,
                new DigestRequesterMail(compact('emailBody'))
            );
        }
    }

    /**
     * @param Fund $fund
     * @param string $targetEvent
     * @param array $otherEvents
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Collection
     */
    protected function getEvents(
        Fund $fund,
        string $targetEvent,
        array $otherEvents
    ) {
        $query = EventLog::eventsOfTypeQuery(Fund::class, $fund->id);

        $logsApprovedBudget = $query->whereIn('event', $otherEvents)->where(
            'created_at', '>=', $this->getFundDigestTime($fund)
        )->get()->groupBy('loggable_id');

        return $logsApprovedBudget->filter(static function(
            Collection $group
        ) use ($targetEvent) {
            $group = $group->sortBy('created_at');
            return ($group->first()->event === $group->last()->event) &&
                $group->last()->event === $targetEvent;
        })->map(static function(Collection $group) {
            return $group->sortBy('created_at')->last();
        })->flatten(1)->pluck('data');
    }

    /**
     * @param Collection|Fund[] $funds
     * @return MailBodyBuilder[]
     */
    public function buildFundProvidersMailBody($funds): array
    {
        $self = $this;

        return $funds->reduce(static function($array, Fund $fund) use ($self) {
            $events = $self->getEvents($fund, Fund::EVENT_PROVIDER_APPROVED_BUDGET, [
                Fund::EVENT_PROVIDER_APPROVED_BUDGET,
                Fund::EVENT_PROVIDER_REVOKED_BUDGET
            ]);

            if ($events->count() > 0) {
                $array[$fund->id] = new MailBodyBuilder();
                $array[$fund->id]->h3(sprintf(
                    "%s zijn goedgekeurd %s nieuwe aanbieder(s) voor %s",
                    $fund->organization->name,
                    $events->count(),
                    $fund->name
                ));

                $text = $events->map(static function(array $data) {
                    return "\n- " . $data['provider_name'];
                })->toArray();

                $array[$fund->id]->text(sprintf(
                    "Uw kunt uw tegoed bij de volgende nieuwe aanbieders: %s" .
                    "\n\nBekijk de webshop voor de volledige context en status.",
                    implode("\n", $text)
                ));
            }

            return $array;
        }, []);
    }

    /**
     * @param Collection|Fund[] $funds
     * @return MailBodyBuilder[]
     */
    private function buildFundProductsMailBody($funds): array
    {
        $self = $this;

        return $funds->reduce(static function($array, Fund $fund) use ($self) {
            $events = $self->getEvents($fund, Fund::EVENT_PRODUCT_APPROVED, [
                Fund::EVENT_PRODUCT_APPROVED,
                Fund::EVENT_PRODUCT_REVOKED
            ])->merge($self->getEvents($fund, Fund::EVENT_PRODUCT_ADDED, [
                Fund::EVENT_PRODUCT_ADDED
            ]));

            if ($events->count() > 0) {
                $productEventsByProvider = $events->groupBy('provider_id');

                $array[$fund->id] = new MailBodyBuilder();
                $array[$fund->id]->h3(sprintf(
                    "%s zijn geaccepteerd met %s nieuwe product(en) voor %s",
                    $fund->organization->name,
                    $events->count(),
                    $fund->name
                ));

                foreach ($productEventsByProvider as $productEventByProvider) {
                    $array[$fund->id]->h5($productEventByProvider[0]['provider_name']);
                    $textList = $productEventByProvider->map(static function(array $data) {
                        return sprintf("- %s voor €%s", $data['product_name'], $data['product_price_locale']
                        );
                    })->toArray();

                    $array[$fund->id]->text(implode("\n", $textList) . "\n");
                }
            }

            return $array;
        }, []);
    }

    /**
     * @return array
     */
    private function getIdentitiesWithFunds(): array
    {
        $identityFunds = [];

        $vouchers = Voucher::where(static function(Builder $builder) {
            $builder->whereNull('product_id');
        })->orWhere(static function(Builder $builder) {
            $builder->whereNotNull('product_id');
            $builder->doesntHave('transactions');
        })->get()->filter(static function(Voucher $voucher) {
            return !$voucher->used;
        });

        $vouchersByIdentities = $vouchers->groupBy('identity_address');

        foreach ($vouchersByIdentities as $identity_address => $vouchersByIdentity) {
            $identity = Identity::findByAddress($identity_address);

            if (!empty($identity)) {
                $funds = $vouchersByIdentity->pluck('fund_id')->unique()->toArray();
                $identityFunds[] = compact('identity', 'funds');
            }
        }

        return $identityFunds;
    }

    /**
     * @param Fund $fund
     * @return \Carbon\Carbon|\Illuminate\Support\Carbon
     */
    public function getFundDigestTime(
        Fund $fund
    ) {
        return $fund->lastDigestOfType('requester')->created_at ?? now()->subDay();
    }

    /**
     * @param Fund $fund
     */
    protected function updateLastDigest(Fund $fund): void {
        $fund->digests()->create([
            'type' => 'requester'
        ]);
    }
}