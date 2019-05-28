<?php

namespace App\Http\Controllers\Api\Platform;

use App\Events\Vouchers\VoucherCreated;
use App\Http\Requests\Api\Platform\Vouchers\ShareProductVoucherRequest;
use App\Http\Requests\Api\Platform\Vouchers\StoreProductVoucherRequest;
use App\Http\Resources\Provider\ProviderVoucherResource;
use App\Http\Resources\VoucherResource;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\VoucherToken;
use App\Models\VoucherTransaction;
use App\Http\Controllers\Controller;

class VouchersController extends Controller
{
    /**
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index() {
        $this->authorize('index', Voucher::class);

        return VoucherResource::collection(Voucher::query()->where([
            'identity_address' => auth()->user()->getAuthIdentifier()
        ])->get()->load(VoucherResource::$load));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreProductVoucherRequest $request
     * @return VoucherResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreProductVoucherRequest $request
    ) {
        $this->authorize('store', Voucher::class);

        /** @var Product $product */
        /** @var Voucher $voucher */
        $product = Product::query()->find($request->input('product_id'));

        /** @var VoucherToken $voucherToken */
        $voucherToken = VoucherToken::query()->where([
            'address' => $request->input('voucher_address')
        ])->first() ?? abort(404);

        $voucher = $voucherToken->voucher ?? abort(404);

        $this->authorize('reserve', $product);

        $voucherExpireAt = $voucher->fund->end_date->gt($product->expire_at) ? $product->expire_at : $voucher->fund->end_date;

        $voucher = Voucher::create([
            'identity_address'  => auth()->user()->getAuthIdentifier(),
            'parent_id'         => $voucher->id,
            'fund_id'           => $voucher->fund_id,
            'product_id'        => $product->id,
            'amount'            => $product->price,
            'expire_at'         => $voucherExpireAt
        ]);

        VoucherCreated::dispatch($voucher);

        return new VoucherResource($voucher->load(VoucherResource::$load));
    }

    /**
     * Display the specified resource.
     *
     * @param VoucherToken $voucherToken
     * @return VoucherResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        VoucherToken $voucherToken
    ) {
        $this->authorize('show', $voucherToken->voucher);

        return new VoucherResource(
            $voucherToken->voucher->load(VoucherResource::$load)
        );
    }

    /**
     * @param VoucherToken $voucherToken
     * @return ProviderVoucherResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function provider(
        VoucherToken $voucherToken
    ) {
        $this->authorize('useAsProvider', $voucherToken->voucher);

        $voucherToken->voucher->setAttribute('address', $voucherToken->address);

        return new ProviderVoucherResource($voucherToken->voucher);
    }

    /**
     * Send target voucher to user email.
     *
     * @param VoucherToken $voucherToken
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function sendEmail(
        VoucherToken $voucherToken
    ) {
        $this->authorize('show', $voucherToken->voucher);

        $voucherToken->voucher->sendToEmail();
    }

    /**
     * Share product voucher to email.
     *
     * @param VoucherToken $voucherToken
     * @param ShareProductVoucherRequest $request
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function shareVoucher(
        VoucherToken $voucherToken,
        ShareProductVoucherRequest $request
    ) {
        $this->authorize('show', $voucherToken->voucher);

        $voucherToken->voucher->shareVoucherEmail(
            $request->input('reason'),
            (bool)$request->get('send_copy', false)
        );
    }

    public function destroy(
        VoucherToken $voucherToken
    ) {
        $this->authorize('show', $voucherToken->voucher);

        $voucher = Voucher::query()->where([
            'identity_address' => $voucherToken->voucher->identity_address
        ])->where('parent_id', '<>', '')->first() ?? abort(404);

        if(null !== VoucherTransaction::query()->where([
            'voucher_id' => $voucherToken->voucher->id
        ])->first())
        {
            abort(404);
        }

        $voucher->delete();

        return compact('success');
    }
}
