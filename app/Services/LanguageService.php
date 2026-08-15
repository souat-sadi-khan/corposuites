<?php

namespace App\Services;

use App\Models\Lang\Language;
use App\Models\Lang\TranslationGroup;
use App\Models\Lang\TranslationKey;
use App\Models\Lang\TranslationValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LanguageService
{
    public function create(array $data): Language
    {
        return DB::transaction(function () use ($data) {

            /*
            Create Language
            */

            $language = Language::create($data);

            /*
            Copy All Translation Keys
            */

            $keys = TranslationKey::all();

            $rows = [];

            foreach ($keys as $key) {

                $rows[] = [

                    'language_id' => $language->id,

                    'translation_key_id' => $key->id,

                    'value' => null,

                    'created_at' => now(),

                    'updated_at' => now()

                ];

            }

            TranslationValue::insert($rows);

            /*
            Clear Cache
            */
            app(TranslationService::class)->clear();
            return $language;
        });
    }

    public function update(Language $language, array $data): Language
    {
        return DB::transaction(function () use ($language, $data) {

            /*
            Update Language
            */

            $language->update($data);

            /*
            Clear Cache
            */
            app(TranslationService::class)->clear();
            return $language;
        });
    }

    public function clear(Language $language): void
    {
        Cache::forget(
            "translations.{$language->code}"
        );
    }

    public function rebuild(Language $language): void
    {
        Cache::rememberForever(
            "translations.{$language->code}",
            function () use ($language) {

                return $language
                    ->translations()
                    ->with('translationKey')
                    ->get()
                    ->pluck(
                        'value',
                        'translationKey.key'
                    )
                    ->toArray();

            }
        );
    }

    public function refresh(Language $language): void
    {
        $this->clear($language);
        $this->rebuild($language);
    }

    public function statistics(Language $language): array
    {
        $total = $language
            ->translationValues()
            ->count();

        $completed = $language
            ->translationValues()
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->count();

        $pending = $total - $completed;

        $percentage = $total
            ? round(($completed / $total) * 100)
            : 0;

        return compact(
            'total',
            'completed',
            'pending',
            'percentage'
        );
    }

    public function groups(Language $language)
    {
        return TranslationGroup::withCount([
            'translationKeys',

            'translationKeys as completed_count' => function ($query) use ($language) {

                $query->whereHas('translationValues', function ($q) use ($language) {

                    $q->where('language_id', $language->id)
                        ->whereNotNull('value')
                        ->where('value', '!=', '');

                });

            }

        ])->orderBy('name')->get();
    }

    public function datatable(
        Request $request,
        Language $language
    ) {
        //
        // Next step
        //
    }

    public function updateTranslation(
        TranslationValue $translation,
        ?string $value
    ) {
        DB::transaction(function () use (
            $translation,
            $value
        ) {

            $translation->update([
                'value' => $value
            ]);

            $this->refresh($translation->language);

        });

        return response()->json([
            'success' => true,
            'message' => 'Translation updated successfully.'
        ]);
    }

    public function bulkUpdate(array $translations)
    {
        DB::transaction(function () use (
            $translations
        ) {

            foreach ($translations as $item) {

                $translation = TranslationValue::findOrFail(
                    $item['id']
                );

                $translation->update([
                    'value' => $item['value']
                ]);

                $this->refresh($translation->language);
            }

        });

        return response()->json([
            'success' => true
        ]);
    }

    public function copyEnglish($id)
    {
        //
        // Next step
        //
    }

    public function restore($id)
    {
        //
        // Next step
        //
    }

}
