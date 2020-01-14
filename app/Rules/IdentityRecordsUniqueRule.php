<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IdentityRecordsUniqueRule implements Rule
{
    private $recordType;
    private $recordRepo;

    /**
     * Create a new rule instance.
     *
     * @param string $recordType
     * @return void
     */
    public function __construct(
        $recordType
    ) {
        $this->recordType = $recordType;
        $this->recordRepo = resolve('forus.services.record');
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     * @throws \Exception
     */
    public function passes($attribute, $value)
    {
        return $this->recordRepo->isRecordUnique($this->recordType, $value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.unique_record', [
            'attribute' => trans('validation.attributes.' . $this->recordType)
        ]);
    }
}
