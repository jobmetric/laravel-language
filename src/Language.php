<?php

namespace JobMetric\Language;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use JobMetric\Language\Events\Language\LanguageStoredEvent;
use JobMetric\Language\Events\Language\LanguageDeletedEvent;
use JobMetric\Language\Events\Language\LanguageDeletingEvent;
use JobMetric\Language\Events\Language\LanguageUpdatedEvent;
use JobMetric\Language\Exceptions\LanguageDataNotExist;
use JobMetric\Language\Http\Requests\StoreLanguageRequest;
use JobMetric\Language\Http\Requests\UpdateLanguageRequest;
use JobMetric\Language\Http\Resources\LanguageResource;
use JobMetric\Language\Models\Language as LanguageModel;
use JobMetric\PackageCore\Output\Response;
use RuntimeException;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;

class Language
{
    /**
     * Build a query for languages with allowed fields, filters, and sorts.
     *
     * @param array<string, mixed> $filter Key-value filter conditions.
     *
     * @return QueryBuilder
     */
    public function query(array $filter = []): QueryBuilder
    {
        $fields = [
            'name',
            'flag',
            'locale',
            'direction',
            'calendar',
            'first_day_of_week',
            'status',
            'created_at',
            'updated_at',
        ];

        return QueryBuilder::for(LanguageModel::class)
            ->allowedFields($fields)
            ->allowedSorts($fields)
            ->allowedFilters($fields)
            ->defaultSort('-id')
            ->where($filter);
    }

    /**
     * Paginate languages based on the given filter.
     *
     * @param array<string, mixed> $filter Key-value filter conditions.
     * @param int $page_limit Number of results per page.
     *
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter = [], int $page_limit = 15): LengthAwarePaginator
    {
        return $this->query($filter)->paginate($page_limit);
    }

    /**
     * Retrieve all languages matching the given filter.
     *
     * @param array<string, mixed> $filter Key-value filter conditions.
     *
     * @return Collection<int, LanguageModel>
     */
    public function all(array $filter = []): Collection
    {
        return $this->query($filter)->get();
    }

    /**
     * Store a new language in the database.
     *
     * @param array<string, mixed> $input The input data for creating a language.
     *
     * @return Response
     * @throws Throwable
     */
    public function store(array $input): Response
    {
        $validated = dto($input, StoreLanguageRequest::class);

        if ($validated instanceof Response) {
            return $validated;
        }

        return DB::transaction(function () use ($validated) {
            $language = LanguageModel::create($validated);

            event(new LanguageStoredEvent($language, $validated));

            return Response::make(true, trans('language::base.messages.created'), LanguageResource::make($language), 201);
        });
    }

    /**
     * Update an existing language in the database.
     *
     * @param int $language_id The ID of the language to update.
     * @param array<string, mixed> $input The updated data for the language.
     *
     * @return Response
     */
    public function update(int $language_id, array $input): Response
    {
        $validated = dto($input, UpdateLanguageRequest::class, [
            'language_id' => $language_id,
        ]);

        if ($validated instanceof Response) {
            return $validated;
        }

        return DB::transaction(function () use ($language_id, $validated) {
            /** @var LanguageModel $language */
            $language = LanguageModel::findOrFail($language_id)->fill($validated)->save();

            event(new LanguageUpdatedEvent($language, $validated));

            return Response::make(true, trans('language::base.messages.updated'), LanguageResource::make($language));
        });
    }

    /**
     * Delete a language from the database.
     *
     * @param int $language_id The ID of the language to delete.
     *
     * @return Response
     */
    public function delete(int $language_id): Response
    {
        return DB::transaction(function () use ($language_id) {
            $language = LanguageModel::findOrFail($language_id);

            event(new LanguageDeletingEvent($language));

            $data = LanguageResource::make($language);

            $language->delete();

            event(new LanguageDeletedEvent($language));

            return Response::make(true, trans('language::base.messages.deleted'), $data);
        });
    }

    /**
     * Add a language from the predefined data file by locale.
     *
     * @param string $locale The locale code to add (e.g., "en", "fa").
     *
     * @return void
     * @throws Throwable
     */
    public function addLanguageData(string $locale): void
    {
        $languages = require realpath(__DIR__ . '/../data/languages.php');

        if (!array_key_exists($locale, $languages)) {
            throw new LanguageDataNotExist($locale);
        }

        LanguageModel::create($languages[$locale]);
    }

    /**
     * Get a list of available flag images with their formatted names.
     *
     * @return array<int, array{value: string, name: string}>
     * @throws Throwable
     */
    public function getFlags(): array
    {
        return cache()->remember('language.flags.list.v1', now()->addDay(), function () {
            $path = public_path('assets/vendor/language/flags');

            if (!is_dir($path)) {
                throw new RuntimeException("The directory {$path} does not exist. Please ensure the flags directory is present.");
            }

            $files = @scandir($path) ?: [];
            $flags = [];

            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'svg') {
                    $nameWithoutExtension = pathinfo($file, PATHINFO_FILENAME);

                    // Directly format the name without separate method
                    $formattedName = ucwords(trim(str_replace(['-', '_'], ' ', $nameWithoutExtension)));

                    $flags[] = [
                        'value' => $file,
                        'name' => $formattedName,
                    ];
                }
            }

            usort($flags, static fn($a, $b) => strnatcasecmp($a['name'], $b['name']));

            return $flags;
        });
    }
}
