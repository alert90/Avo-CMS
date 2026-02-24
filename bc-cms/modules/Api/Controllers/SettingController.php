<?php

namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    public function getLanguages()
    {
        try {
            $languages = \Modules\Language\Models\Language::getActive();
            
            $data = $languages->map(function($lang) {
                return [
                    'id' => $lang->id,
                    'locale' => $lang->locale,
                    'name' => $lang->name,
                    'flag' => $lang->flag,
                    'status' => $lang->status,
                ];
            });
            
            return $this->sendSuccess([
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
    
    public function changeLanguage(Request $request)
    {
        try {
            $request->validate([
                'locale' => 'required|string'
            ]);
            
            $user = Auth::user();
            $locale = $request->locale;
            
            // Validate if language exists and is active
            $language = \Modules\Language\Models\Language::where('locale', $locale)
                ->where('status', 'publish')
                ->first();
                
            if (!$language) {
                return $this->sendError(__('Language not available'));
            }
            
            // Update user language preference
            $user->locale = $locale;
            $user->save();
            
            // Set application locale for this request
            App::setLocale($locale);
            session(['website_locale' => $locale]);
            
            return $this->sendSuccess([
                'message' => __('Language changed successfully'),
                'locale' => $locale
            ]);
            
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function setLanguagePost(Request $request)
    {
        try {
            $request->validate([
                'locale' => 'required|string'
            ]);
            
            $locale = $request->locale;
            
            // Validate if language exists and is active
            $language = \Modules\Language\Models\Language::where('locale', $locale)
                ->where('status', 'publish')
                ->first();
                
            if (!$language) {
                return $this->sendError(__('Language not available'));
            }
            
            // Set application locale for this request
            App::setLocale($locale);
            session(['website_locale' => $locale]);
            
            return $this->sendSuccess([
                'message' => __('Language changed successfully'),
                'locale' => $locale
            ]);
            
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function setCurrencyPost(Request $request)
    {
        try {
            $request->validate([
                'currency' => 'required|string'
            ]);
            
            $currency = $request->currency;
            
            // Set currency in session
            session(['current_currency' => $currency]);
            
            return $this->sendSuccess([
                'message' => __('Currency changed successfully'),
                'currency' => $currency
            ]);
            
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function getCurrentSettings()
    {
        return $this->sendSuccess([
            'locale' => session('website_locale', config('app.locale')),
            'currency' => session('current_currency', 'tzs')
        ]);
    }
}