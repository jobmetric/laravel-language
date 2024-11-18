<?php

namespace JobMetric\Language;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use JobMetric\Category\Exceptions\LanguageDataNotExist;
use JobMetric\Language\Events\Language\LanguageDeleteEvent;
use JobMetric\Language\Events\Language\LanguageStoreEvent;
use JobMetric\Language\Events\Language\LanguageUpdateEvent;
use JobMetric\Language\Http\Requests\StoreLanguageRequest;
use JobMetric\Language\Http\Requests\UpdateLanguageRequest;
use JobMetric\Language\Http\Resources\LanguageResource;
use JobMetric\Language\Models\Language as LanguageModel;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;

class Language
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Create a new Setting instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get the specified language.
     *
     * @param array $filter
     * @return QueryBuilder
     */
    public function query(array $filter = []): QueryBuilder
    {
        $fields = ['id', 'name', 'flag', 'locale', 'direction', 'status'];

        return QueryBuilder::for(LanguageModel::class)
            ->allowedFields($fields)
            ->allowedSorts($fields)
            ->allowedFilters($fields)
            ->defaultSort('-id')
            ->where($filter);
    }

    /**
     * Paginate the specified language.
     *
     * @param array $filter
     * @param int $page_limit
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter = [], int $page_limit = 15): LengthAwarePaginator
    {
        return $this->query($filter)->paginate($page_limit);
    }

    /**
     * Get all languages.
     *
     * @param array $filter
     * @return Collection
     */
    public function all(array $filter = []): Collection
    {
        return $this->query($filter)->get();
    }

    /**
     * Store the specified language.
     *
     * @param array $data
     * @return array
     * @throws Throwable
     */
    public function store(array $data): array
    {
        $validator = Validator::make($data, (new StoreLanguageRequest)->rules());
        if ($validator->fails()) {
            $errors = $validator->errors()->all();

            return [
                'ok' => false,
                'message' => trans('language::base.validation.errors'),
                'errors' => $errors,
                'status' => 422
            ];
        }

        return DB::transaction(function () use ($data) {
            $language = new LanguageModel;
            $language->name = $data['name'];
            $language->flag = $data['flag'] ?? null;
            $language->locale = $data['locale'];
            $language->direction = $data['direction'];
            $language->status = $data['status'] ?? true;
            $language->save();

            event(new LanguageStoreEvent($language, $data));

            return [
                'ok' => true,
                'message' => trans('language::base.messages.created'),
                'data' => LanguageResource::make($language),
                'status' => 201
            ];
        });
    }

    /**
     * Update the specified language.
     *
     * @param int $language_id
     * @param array $data
     * @return array
     */
    public function update(int $language_id, array $data): array
    {
        $validator = Validator::make($data, (new UpdateLanguageRequest)->setLanguageId($language_id)->setData($data)->rules());
        if ($validator->fails()) {
            $errors = $validator->errors()->all();

            return [
                'ok' => false,
                'message' => trans('language::base.validation.errors'),
                'errors' => $errors,
                'status' => 422
            ];
        }

        return DB::transaction(function () use ($language_id, $data) {
            /**
             * @var LanguageModel $language
             */
            $language = LanguageModel::query()->where('id', $language_id)->first();

            if (!$language) {
                return [
                    'ok' => false,
                    'message' => trans('language::base.validation.errors'),
                    'errors' => [
                        trans('language::base.validation.language_not_found')
                    ],
                    'status' => 404
                ];
            }

            if (array_key_exists('name', $data)) {
                $language->name = $data['name'];
            }

            if (array_key_exists('flag', $data)) {
                $language->flag = $data['flag'];
            }

            if (array_key_exists('locale', $data)) {
                $language->locale = $data['locale'];
            }

            if (array_key_exists('direction', $data)) {
                $language->direction = $data['direction'];
            }

            if (array_key_exists('status', $data)) {
                $language->status = $data['status'];
            }

            $language->save();

            event(new LanguageUpdateEvent($language, $data));

            return [
                'ok' => true,
                'message' => trans('language::base.messages.updated'),
                'data' => LanguageResource::make($language),
                'status' => 200
            ];
        });
    }

    /**
     * Delete the specified language.
     *
     * @param int $language_id
     * @return array
     */
    public function delete(int $language_id): array
    {
        return DB::transaction(function () use ($language_id) {
            /**
             * @var LanguageModel $language
             */
            $language = LanguageModel::query()->where('id', $language_id)->first();

            if (!$language) {
                return [
                    'ok' => false,
                    'message' => trans('language::base.validation.errors'),
                    'errors' => [
                        trans('language::base.validation.language_not_found')
                    ],
                    'status' => 404
                ];
            }

            $data = LanguageResource::make($language);

            event(new LanguageDeleteEvent($language));

            $language->delete();

            return [
                'ok' => true,
                'message' => trans('language::base.messages.deleted'),
                'data' => $data,
                'status' => 200
            ];
        });
    }

    /**
     * Add Language Data
     *
     * @param string $locale
     *
     * @return void
     * @throws Throwable
     */
    public function addLanguageDate(string $locale): void
    {
        $languages = require realpath(__DIR__ . '/../data/languages.php');

        if (!array_key_exists($locale, $languages)) {
            throw new LanguageDataNotExist($locale);
        }

        $language = new LanguageModel;
        $language->name = $languages[$locale]['name'];
        $language->flag = $languages[$locale]['flag'];
        $language->locale = $languages[$locale]['locale'];
        $language->direction = $languages[$locale]['direction'];
        $language->status = true;

        $language->save();
    }

    /**
     * Get list of flag images with formatted data.
     *
     * @return array
     */
    public function getFlags(): array
    {
        $path = public_path('assets/vendor/language/flags');
        $files = scandir($path);
        $flags = [];

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'svg') {
                $nameWithoutExtension = pathinfo($file, PATHINFO_FILENAME);
                $formattedName = $this->formatName($nameWithoutExtension);

                $flags[] = [
                    'value' => $file,
                    'name' => $formattedName,
                ];
            }
        }

        return $flags;
    }

    /**
     * Format the filename to replace dashes with spaces and capitalize words.
     *
     * @param string $name
     * @return string
     */
    private function formatName(string $name): string
    {
        $name = str_replace('-', ' ', $name);
        return ucwords($name);
    }
}
