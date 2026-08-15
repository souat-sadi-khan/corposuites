<?php

namespace App\Services;

use App\Models\Lang\Language;
use App\Models\Lang\TranslationValue;
use Illuminate\Support\Facades\Cache;

class TranslationService
{
    /**
     * Return all translations of one language.
     */
    public function load(string $languageCode): array
    {
        return Cache::remember(

            config('translation.cache_prefix') . $languageCode,

            now()->addMinutes(
                config('translation.cache_minutes')
            ),

            function () use ($languageCode) {

                $translations = TranslationValue::query()

                    ->join(
                        'translation_keys',
                        'translation_values.translation_key_id',
                        '=',
                        'translation_keys.id'
                    )

                    ->join(
                        'translation_groups',
                        'translation_keys.group_id',
                        '=',
                        'translation_groups.id'
                    )

                    ->join(
                        'languages',
                        'translation_values.language_id',
                        '=',
                        'languages.id'
                    )

                    ->where(
                        'languages.code',
                        $languageCode
                    )

                    ->get([
                        'translation_groups.slug',
                        'translation_keys.key',
                        'translation_values.value'
                ]);

                return $translations
                    ->mapWithKeys(function ($item) {

                        return [

                            $item->slug.'.'.$item->key
                                =>

                            $item->value

                        ];

                    })
                    ->toArray();

            }

        );
    }

    /**
     * Translate key
     */
    public function get(string $key): string
    {
        $lang = session('language', config('translation.default'));

        $translations = $this->load($lang);

        if (!empty($translations[$key])) {

            return $translations[$key];

        }

        /*
         | fallback English
         */

        if ($lang != config('translation.default')) {

            $english = $this->load(
                config('translation.default')
            );

            if (!empty($english[$key])) {

                return $english[$key];

            }

        }

        /*
         | nothing found
         */

        return $key;
    }

    /**
     * Clear Cache
     */

    public function clear(): void
    {
        Language::pluck('code')->each(function ($code) {

            Cache::forget(
                config('translation.cache_prefix') . $code
            );

        });
    }
}
