<?php

namespace App\Rules;

use App\Models\FundProviderProduct;
use App\Models\Product;
use App\Models\Voucher;
use App\Scopes\Builders\FundProviderProductQuery;
use App\Scopes\Builders\ProductQuery;

/**
 * Class ProductIdToReservationRule
 * @package App\Rules
 */
class ProductIdToReservationRule extends BaseRule
{
    protected $messageTransPrefix = 'validation.product_reservation.';
    private $voucherAddress;
    private $priceType;

    /**
     * Create a new rule instance.
     *
     * @param string|null $voucherAddress
     * @param string|null $priceType
     */
    public function __construct(?string $voucherAddress, string $priceType = null)
    {
        $this->priceType = $priceType;
        $this->voucherAddress = $voucherAddress;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string|any  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $product = Product::find($value);
        $voucher = Voucher::findByAddressOrPhysicalCard($this->voucherAddress);

        if (!$this->voucherAddress || !$voucher) {
            return $this->rejectTrans('voucher_address_required');
        }

        if (!$product || !$product->exists) {
            return $this->rejectTrans('product_not_found');
        }

        if (!$product->reservationsEnabled($voucher->fund)) {
            return $this->rejectTrans('reservation_not_enabled');
        }

        if ($product->sold_out) {
            return $this->rejectTrans('product_sold_out');
        }

        if ($this->priceType && ($product->price_type !== $this->priceType)) {
            return $this->rejectTrans('invalid_product_price_type');
        }

        if ($voucher->fund->isTypeBudget() && ($product->price > $voucher->amount_available)) {
            return $this->rejectTrans('not_enough_voucher_funds');
        }

        // validate per-identity limit
        if ($voucher->fund->isTypeSubsidy()) {
            $fundProviderProduct = $product->getSubsidyDetailsForFund($voucher->fund);

            if (!$fundProviderProduct) {
                return $this->rejectTrans('product_not_available');
            }

            if ($fundProviderProduct->stockAvailableForIdentity($voucher->identity_address) < 1) {
                return $this->rejectTrans('no_identity_stock');
            }

            $query = FundProviderProductQuery::whereAvailableForSubsidyVoucherFilter(
                FundProviderProduct::query(),
                $voucher
            );

            if (!$query->where('product_id', $product->id)->exists()) {
                return $this->rejectTrans('no_identity_stock');
            }
        }

        // check validity
        return ProductQuery::approvedForFundsAndActiveFilter(
            Product::query(),
            $voucher->fund_id
        )->where('id', $product->id)->exists();
    }
}