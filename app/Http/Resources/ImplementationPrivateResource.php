<?php

namespace App\Http\Resources;

use App\Models\Implementation;
use Illuminate\Http\Resources\Json\JsonResource;

class ImplementationPrivateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @property Implementation $resource
     * @return array
     */
    public function toArray($request)
    {
        /** @var Implementation $implementation **/
        if (is_null($implementation = $this->resource)) {
            return null;
        }

        return $implementation->only([
            'id', 'key', 'name', 'url_webshop', 'title',
            'description', 'has_more_info_url',
            'more_info_url', 'description_steps',
            'digid_app_id', 'digid_shared_secret',
            'digid_a_select_server', 'digid_enabled',
            'email_from_address', 'email_from_name'
        ]);
    }
}