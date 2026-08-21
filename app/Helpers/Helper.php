<?php

use App\Models\Lang\Language;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

if (!function_exists('get_settings')) {
    function get_settings($key, $default = null)
    {

        $settings = Cache::rememberForever('system_settings', function () {

            return SystemSetting::where('autoload', true)
                ->pluck('value', 'key')
                ->toArray();

        });

        return $settings[$key] ?? $default;

    }
}

if (!function_exists('checkChildActive')) {
    function checkChildActive(array $items): bool
    {
        $currentRoute = Request::route()?->getName();

        foreach ($items as $item) {
            if (!empty($item['route']) && $item['route'] === $currentRoute) {
                return true;
            }

            if (!empty($item['url']) && Request::is(ltrim($item['url'], '/'))) {
                return true;
            }

            if (!empty($item['children']) && checkChildActive($item['children'])) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('clear_settings_cache')) {
    function clear_settings_cache()
    {
        Cache::forget('system_settings');
    }
}

if (!function_exists('tz_list')) {
    function tz_list()
    {
        $zones_array = array();
        $timestamp = time();
        foreach (timezone_identifiers_list() as $key => $zone) {
            date_default_timezone_set($zone);
            $zones_array[$key]['zone'] = $zone;
            $zones_array[$key]['diff_from_GMT'] = 'UTC/GMT ' . date('P', $timestamp);
        }
        return $zones_array;
    }
}

if (!function_exists('split_name')) {
    function split_name($name) {
        $data = [];
        foreach ($name as $value) {
            $per = explode('.', $value->name);
            $data[toWord($per[0])][] = $value->name;
        }

        return $data;
    }
}

if (!function_exists('toWord')) {
    function toWord($word) {
        $word = str_replace('_', ' ', $word);
        $word = str_replace('-', ' ', $word);
        $word = ucwords($word);

        return $word;
    }
}

if (!function_exists('tounderscore')) {
    function tounderscore($text) {
        $text = str_replace(' ', '_', $text);
        $text = str_replace(' ', '_', $text);
        return $text;
    }
}

if (!function_exists('toSpan')) {
    function toSpan($data) {
        $per = explode('.', $data);

        $data = $per[0];
        if(isset($per[1])) {
            $data .= ' - '. $per[1];
        }

        return toWord($data);
    }
}

if (!function_exists('checkChildActive')) {
    function checkChildActive(array $items): bool
    {
        $currentRoute = request()->route()?->getName();
        foreach ($items as $item) {
            if (!empty($item['route']) && $item['route'] === $currentRoute) {
                return true;
            }
            if (!empty($item['url']) && request()->is(ltrim($item['url'], '/'))) {
                return true;
            }
            if (!empty($item['children']) && checkChildActive($item['children'])) {
                return true;
            }
        }

        return false;
    }
}

function checkMenuActive($items) {
    $currentRoute = request()->route()->getName();
    foreach($items as $item) {
        if(!empty($item['children'])) {
            if(checkMenuActive($item['children'])) return true;
        }
        if(!empty($item['route']) && $item['route'] == $currentRoute) {
            return true;
        }
        if(!empty($item['url']) && request()->is(ltrim($item['url'], '/'))) {
            return true;
        }
    }
    return false;
}

/*
|--------------------------------------------------------------------------
| Localization Helpers
|--------------------------------------------------------------------------
*/

if (!function_exists('get_languages')) {
    function get_languages()
    {
        return Language::where('status', 1)
            ->orderBy('name')
            ->get();
    }

}

if (!function_exists('default_language')) {
    function default_language()
    {
        return get_settings('default_language', config('app.locale'));
    }
}

if (!function_exists('admin_language')) {
    function admin_language()
    {
        return get_settings(
            'admin_language',
            default_language()
        );
    }
}

if (!function_exists('fallback_language')) {
    function fallback_language()
    {
        return get_settings(
            'fallback_language',
            config('app.fallback_locale')
        );
    }
}

if (!function_exists('language_switcher_enabled')) {
    function language_switcher_enabled()
    {
        return (bool) get_settings(
            'language_switcher',
            true
        );
    }
}

if (!function_exists('auto_detect_language')) {
    function auto_detect_language()
    {
        return (bool) get_settings(
            'auto_detect_language',
            false
        );
    }
}

if (!function_exists('system_direction')) {
    function system_direction()
    {
        return get_settings(
            'direction',
            'ltr'
        );
    }
}

if (!function_exists('is_rtl')) {
    function is_rtl()
    {
        return system_direction() === 'rtl';
    }
}

if (!function_exists('locale_code')) {
    function locale_code()
    {
        return app()->getLocale();
    }
}

if (!function_exists('set_locale')) {
    function set_locale($locale)
    {
        $available = get_languages()
            ->pluck('code')
            ->toArray();


        if(in_array($locale,$available)) {

            app()->setLocale($locale);

            return true;
        }

        return false;

    }
}


/**
 * Get current application language
 */
if (!function_exists('current_language')) {

    function current_language()
    {
        return Cache::rememberForever('current_language', function () {

            return Language::where('status', 1)
                ->where('is_default', 1)
                ->first();

        });
    }

}


/**
 * Get current locale code
 */
if (!function_exists('current_locale')) {

    function current_locale()
    {
        return app()->getLocale();
    }

}


/**
 * Get available languages
 */
if (!function_exists('available_languages')) {

    function available_languages()
    {

        return Cache::rememberForever('available_languages', function () {

            return Language::where('status',1)
                ->orderBy('name')
                ->get();

        });

    }

}


/**
 * Set application language
 */
if (!function_exists('set_locale')) {

    function set_locale($locale)
    {

        $language = Language::where('iso_code',$locale)
            ->where('status',1)
            ->first();


        if(!$language){
            return false;
        }


        app()->setLocale($language->iso_code);


        session([
            'locale' => $language->iso_code
        ]);


        return true;

    }

}

/**
 * Clear translation cache
 */
if (!function_exists('clear_translation_cache')) {
    function clear_translation_cache()
    {
        Cache::forget('translation_values');
        Cache::forget('available_languages');
        Cache::forget('current_language');
    }
}

/**
 * Check RTL language
 */
if (!function_exists('is_rtl')) {
    function is_rtl()
    {
        $language = current_language();

        if(!$language){
            return false;
        }

        return $language->direction === 'rtl';
    }
}

/**
 * Get direction
 */
if (!function_exists('language_direction')) {
    function language_direction()
    {
        return is_rtl()
            ? 'rtl'
            : 'ltr';

    }
}

/**
 * Format date by system setting
 */
if (!function_exists('format_date')) {
    function format_date($date)
    {
        if(!$date){
            return null;
        }

        $format = get_settings(
            'date_format',
            'Y-m-d'
        );

        return \Carbon\Carbon::parse($date)
            ->format($format);
    }
}

/**
 * Format time by system setting
 */
if (!function_exists('format_time')) {
    function format_time($time)
    {
        if(!$time){
            return null;
        }

        $format = get_settings(
            'time_format',
            '24'
        );

        if($format == '12'){

            return \Carbon\Carbon::parse($time)
                ->format('h:i A');

        }

        return \Carbon\Carbon::parse($time)
            ->format('H:i');

    }
}

/**
 * Format datetime
 */
if (!function_exists('format_datetime')) {
    function format_datetime($datetime)
    {
        if(!$datetime){
            return null;
        }

        return format_date($datetime)
            .' '
            .format_time($datetime);
    }
}

/**
 * Number format helper
 */
if (!function_exists('format_number')) {
    function format_number($number,$decimal = 2)
    {
        $format = get_settings(
            'number_format',
            '1,234.56'
        );

        if($format === '1.234,56'){
            return number_format(
                $number,
                $decimal,
                ',',
                '.'
            );
        }

        return number_format(
            $number,
            $decimal,
            '.',
            ','
        );
    }
}

/**
 * Format an amount using the Currency Settings
 * configured under Settings -> Localization.
 */
if (!function_exists('format_currency')) {
    function format_currency($amount, $decimal = 2)
    {
        $number = format_number($amount, $decimal);

        $showCode = (bool) get_settings('show_currency_code', false);

        if ($showCode) {
            return get_settings('currency', 'USD') . ' ' . $number;
        }

        $symbol = get_settings('currency_symbol', '$');
        $position = get_settings('currency_position', 'before');

        return $position === 'after'
            ? $number . $symbol
            : $symbol . $number;
    }
}

/**
 * Detect browser language
 */
if (!function_exists('detect_browser_language')) {
    function detect_browser_language()
    {
        $language = request()
            ->getPreferredLanguage(
                available_languages()
                    ->pluck('iso_code')
                    ->toArray()
            );


        return $language ?? config('app.locale');

    }
}

/**
 * Get user preferred language
 */
if (!function_exists('user_language')) {
    function user_language()
    {
        if(!auth()->guard('admin')->check()){
            return current_language();
        }

        return Cache::remember(
            'user_language_'.auth()->guard('admin')->id(),
            now()->addHours(12),
            function(){
                return auth()->guard('admin')->user()
                    ->language ?? current_language();

            }
        );
    }
}

/**
 * Set user language
 */
if (!function_exists('set_user_language')) {

    function set_user_language($locale)
    {
        if(!auth()->guard('admin')->check()){
            return false;
        }

        $language = Language::where('iso_code',$locale)
            ->where('status',1)
            ->first();

        if(!$language){
            return false;
        }

        auth()->guard('admin')->user()->update([
            'language_id'=>$language->id
        ]);

        Cache::forget(
            'user_language_'.auth()->guard('admin')->id()
        );

        return true;
    }
}

/**
 * Language switch URL
 */
if (!function_exists('language_switch_url')) {
    function language_switch_url($locale)
    {
        return url()->current()
            .'?lang='
            .$locale;

    }
}

/**
 * Current language active check
 */
if (!function_exists('is_language')) {

    function is_language($locale)
    {
        return current_locale() === $locale;

    }
}

/**
 * Missing translation logger
 */
if (!function_exists('log_missing_translation')) {

    function log_missing_translation($key)
    {
        if(app()->environment('production')){
            return;
        }

        Log::warning(
            'Missing translation key',
            [
                'key'=>$key,
                'locale'=>current_locale(),
                'url'=>request()->fullUrl(),
                'user'=>auth()->guard('admin')->id()
            ]
        );

    }

}

/**
 * Locale from request
 */
if (!function_exists('request_locale')) {

    function request_locale()
    {
        return request()
            ->query('lang')
            ??
            session('locale')
            ??
            detect_browser_language();

    }

}

/**
 * Apply request locale
 */
if (!function_exists('apply_request_locale')) {

    function apply_request_locale()
    {
        $locale = request_locale();

        if(!$locale){
            return;
        }

        if(
            available_languages()
            ->where('iso_code',$locale)
            ->count()
        ){

            app()->setLocale($locale);

            session([
                'locale'=>$locale
            ]);

        }

    }

}

/**
 * Locale attributes for HTML tag
 */
if (!function_exists('html_locale_attributes')) {

    function html_locale_attributes()
    {
        return sprintf(
            'lang="%s" dir="%s"',
            current_locale(),
            language_direction()
        );
    }
}

/**
 * Translation cache rebuild
 */
if (!function_exists('rebuild_translation_cache')) {

    function rebuild_translation_cache()
    {
        Cache::forget(
            'translation_values'
        );

        Cache::forget(
            'translation_keys'
        );

        return true;
    }
}

/**
 * Localization debug info
 */
if (!function_exists('localization_info')) {

    function localization_info()
    {
        return [
            'locale'=>current_locale(),
            'direction'=>language_direction(),
            'language'=>optional(
                current_language()
            )->name,
            'browser'=>detect_browser_language(),
            'available'=>available_languages()
                ->pluck('iso_code')
                ->toArray()
        ];
    }
}
