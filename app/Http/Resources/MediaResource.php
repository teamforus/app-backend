<?php

namespace App\Http\Resources;

use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Models\MediaPreset;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class MediaResource
 * @property Media $resource
 * @package App\Http\Resources
 */
class MediaResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request|any  $request
     * @return array
     */
    public function toArray($request): ?array
    {
        if (is_null($media = $this->resource)) {
            return null;
        }

        $sizes = $media->presets->filter(static function(MediaPreset $preset) {
            return $preset->key !== 'original';
        })->keyBy('key')->map(static function(MediaPreset $preset) {
            return $preset->urlPublic();
        });

        return array_merge($media->only([
            'identity_address', 'original_name', 'type', 'ext', 'uid', 'dominant_color',
            'is_dark',
        ]), compact('sizes'));
    }
}
