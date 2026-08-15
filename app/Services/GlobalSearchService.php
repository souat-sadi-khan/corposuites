<?php

namespace App\Services;

class GlobalSearchService
{
    public function search($keyword)
    {
        if(strlen($keyword)<3){

            return [

                'menus'=>[],
                'database'=>[]

            ];

        }

        return [

            'menus'=>$this->menus($keyword),

            'database'=>$this->database($keyword),

        ];
    }

    protected function menus($keyword)
    {
        return collect(config('global-search.menus'))

            ->filter(function($menu) use($keyword){

                return str_contains(

                    strtolower($menu['title']),
                    strtolower($keyword)

                );

            })

            ->map(function($menu){
                return [
                    'title' =>  $menu['title'],
                    'icon'  =>  $menu['icon'],
                    'url'   =>  route($menu['url'])
                ];
            })

            ->values();

    }

    protected function database($keyword)
    {
        $results = collect();

        foreach(config('global-search.models') as $model){
            if(!is_subclass_of($model,\App\Contracts\GlobalSearchable::class)){
                continue;
            }

            foreach($model::globalSearch($keyword) as $record){
                $results->push([
                    'type'  =>  $model::globalSearchTitle(),
                    'title' =>  $record->name ?? $record->title,
                    'icon'  =>  $model::globalSearchIcon(),
                    'url'   =>  $model::globalSearchRoute($record)
                ]);
            }
        }

        return $results;
    }
}
