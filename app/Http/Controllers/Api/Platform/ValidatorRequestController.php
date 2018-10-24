<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\ValidatorRequest\StoreValidatorRequestRequest;
use App\Http\Resources\ValidatorRequestResource;
use App\Models\Validator;
use App\Models\ValidatorRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ValidatorRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request)
    {
        $this->authorize('index', ValidatorRequest::class);

        $validatorRequest = ValidatorRequest::getModel()->where([
            'identity_address' => auth()->user()->getAuthIdentifier()
        ])->get();

        return ValidatorRequestResource::collection($validatorRequest);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreValidatorRequestRequest $request
     * @return ValidatorRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(StoreValidatorRequestRequest $request)
    {
        $this->authorize('request', ValidatorRequest::class);

        $validatorRequest = ValidatorRequest::create([
            'record_id' => $request->input('record_id'),
            'identity_address' => auth()->user()->getAuthIdentifier(),
            'validator_id' => $request->input('validator_id'),
            'state' => 'pending'
        ]);

        resolve('forus.services.mail_notification')->newValidationRequest(
            $validatorRequest->validator->identity_address,
            resolve('forus.services.record')->primaryEmailByAddress(
                $validatorRequest->validator->identity_address
            )
        );

        return new ValidatorRequestResource($validatorRequest);
    }

    /**
     * Display the specified resource.
     *
     * @param ValidatorRequest $validatorRequest
     * @return ValidatorRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(ValidatorRequest $validatorRequest)
    {
        $this->authorize('show', $validatorRequest);

        return new ValidatorRequestResource($validatorRequest);
    }
}
