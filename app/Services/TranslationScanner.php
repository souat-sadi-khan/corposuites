<?php

namespace App\Services;

use App\Models\Lang\Language;
use App\Models\Lang\TranslationGroup;
use App\Models\Lang\TranslationKey;
use App\Models\Lang\TranslationValue;
use Illuminate\Support\Facades\File;

class TranslationScanner
{
    /**
     * Scan whole project
     */
    public function scan()
    {
        $files = collect()

            ->merge(
                File::allFiles(resource_path('views'))
            )

            ->merge(
                File::allFiles(app_path())
            );

        foreach ($files as $file) {

            $this->scanFile(
                $file->getRealPath()
            );

        }
    }

    /**
     * Scan one file
     */

    protected function scanFile($path)
    {
        $content = File::get($path);

        preg_match_all(
            '/(?<![A-Za-z0-9_])t\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            $content,
            $matches
        );

        foreach ($matches[1] as $key) {

            $this->storeKey($key);

        }

    }

    /**
     * Save key
     */

    protected function storeKey($fullKey)
    {
        if (!str_contains($fullKey,'.')) {

            return;

        }

        [$groupSlug,$key]=explode('.',$fullKey,2);

        $group=TranslationGroup::firstOrCreate(

            [

                'slug'=>$groupSlug

            ],

            [

                'name'=>ucfirst($groupSlug)

            ]

        );

        $translationKey=TranslationKey::firstOrCreate(

            [

                'group_id'=>$group->id,

                'key'=>$key

            ]

        );

        $languages=Language::all();

        foreach($languages as $language){

            TranslationValue::firstOrCreate(

                [

                    'language_id'=>$language->id,

                    'translation_key_id'=>$translationKey->id

                ],

                [

                    /*
                     English = key

                     Others = NULL
                    */

                    'value'=>$language->is_default
                        ? ucwords(
                            str_replace(
                                '_',
                                ' ',
                                $key
                            )
                        )
                        : null

                ]

            );

        }

    }

}
