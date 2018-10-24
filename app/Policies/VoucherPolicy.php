<?php

namespace App\Policies;

use App\Models\Voucher;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\HandlesAuthorization;

class VoucherPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function store(string $identity_address) {
        return !empty($identity_address);
    }

    public function show(string $identity_address, Voucher $voucher) {
        return strcmp(
            $identity_address,
            $voucher->identity_address
            ) == 0;
    }

    /**
     * @param string $identity_address
     * @param Voucher $voucher
     * @return bool
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function useAsProvider(string $identity_address, Voucher $voucher) {
        if ($voucher->type == 'regular') {
            $organizations = $voucher->fund->provider_organizations_approved;

            return $organizations->pluck(
                'provider_identities'
            )->flatten()->pluck(
                'identity_address'
            )->search($identity_address) !== false;
        } else if ($voucher->type == 'product') {
            // Product vouchers can have no more than 1 transaction
            if ($voucher->transactions->count() > 0) {
                throw new AuthorizationException(trans(
                    'validation.voucher.product_voucher_used'
                ));
            }

            // The product should not be expired
            if ($voucher->product->expired) {
                throw new AuthorizationException(trans(
                    'validation.voucher.product_expired'
                ));
            }

            // The product should not be sold out
            if ($voucher->type != 'product' && $voucher->product->sold_out) {
                throw new AuthorizationException(trans(
                    'validation.voucher.product_sold_out'
                ));
            }

            // The identity should be allowed to scan voucher for
            // the provider organization
            return $voucher->product->organization->provider_identities->pluck(
                    'identity_address'
                )->search($identity_address) !== false;
        }

        return false;
    }
}
