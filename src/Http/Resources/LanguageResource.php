<?php

namespace JobMetric\Language\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed id
 * @property mixed name
 * @property mixed flag
 * @property mixed locale
 * @property mixed direction
 * @property mixed calendar
 * @property mixed status
 * @property mixed created_at
 * @property mixed updated_at
 */
class LanguageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'flag' => $this->flag,
            'locale' => $this->locale,
            'direction' => $this->direction,
            'calendar' => $this->calendar,
            'calendar_trans' => trans('language::base.calendar_type.' . $this->calendar),
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
