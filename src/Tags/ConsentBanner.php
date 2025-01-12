<?php

namespace Mohammedshuaau\EnhancedAnalytics\Tags;

use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Log;
use Statamic\Facades\File;
use Statamic\Tags\Tags;

class ConsentBanner extends Tags
{
    protected static $handle = 'enhanced_analytics';

    /**
     * The {{ enhanced_analytics:consent_banner }} tag
     */
    public function consent_banner()
    {
        return $this->index();
    }

    /**
     * The {{ enhanced_analytics }} tag
     */
    public function index()
    {
        try {
            // Get the template content directly
            $templatePath = $this->getTemplatePath();
            if (!File::exists($templatePath)) {
                throw new \Exception("Template not found at: {$templatePath}");
            }

            // Get the template content
            $content = File::get($templatePath);

            // Get the context data
            $context = array_merge($this->context->all(), [
                'config' => [
                    'enhanced-analytics' => [
                        'tracking' => [
                            'consent' => [
                                'banner' => config('enhanced-analytics.tracking.consent.banner')
                            ]
                        ]
                    ]
                ]
            ]);

            // Parse it with Antlers
            return \Statamic\Facades\Antlers::parse($content, $context);
        } catch (\Exception $e) {
            Log::error('Error rendering consent banner', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return '<!-- Error rendering consent banner: ' . $e->getMessage() . ' -->';
        }
    }

    protected function getTemplatePath()
    {
        return __DIR__ . '/../../resources/views/components/consent-banner.antlers.html';
    }

    public function wildcard($method)
    {
        if ($method === 'consent_banner') {
            return $this->consent_banner();
        }

        return $this->index();
    }

    /**
     * The {{ AltCookies:AddonAssets }} tag.
     * Puts the Vite assets on the frontent
     * @return string|array
     */
    public function AddonAssets()
    {
        $vite = (new Vite)->useHotfile( __DIR__ . '/../../resources/dist/hot')->useBuildDirectory('vendor/enhanced-analytics/dist');
        //$assets = sprintf('<script data-cfasync=“false” type="module" src="%s"></script>', $vite->asset('resources/js/consent-banner.js'));
        $assets = sprintf('<link rel="stylesheet" href="%s" />', $vite->asset('resources/css/enhanced-analytics.css'));
        return $assets;
    }
}
