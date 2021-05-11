<?php

declare(strict_types=1);

namespace Bolt\Configuration\Parser;

use Bolt\Common\Arr;
use Tightenco\Collect\Support\Collection;

class GeneralParser extends BaseParser
{
    public function __construct(string $projectDir, string $initialFilename = 'config.yaml')
    {
        parent::__construct($projectDir, $initialFilename);
    }

    /**
     * Read and parse the config.yaml and config_local.yaml configuration files.
     */
    public function parse(): Collection
    {
        $defaultconfig = $this->getDefaultConfig();
        $tempconfig = $this->parseConfigYaml($this->getInitialFilename());
        $tempconfiglocal = $this->parseConfigYaml($this->getFilenameLocalOverrides(), true);
        $general = Arr::replaceRecursive($defaultconfig, Arr::replaceRecursive($tempconfig, $tempconfiglocal));

        if (! isset($general['date_format'])) {
            $general['date_format'] = 'F j, Y H:i';
        }

        if (! isset($general['timezone'])) {
            $general['timezone'] = 'UTC';
        }

        if (! isset($general['curl_options'])) {
            $general['curl_options'] = [
                'verify_peer' => true,
            ];
        }

        if (! isset($general['query_search'])) {
            $general['query_search'] = true;
        }

        if (! is_array($general['notfound'])) {
            $general['notfound'] = [$general['notfound']];
        }
        if (! is_array($general['maintenance'])) {
            $general['maintenance'] = [$general['maintenance']];
        }
        if (! is_array($general['forbidden'])) {
            $general['forbidden'] = [$general['forbidden']];
        }
        if (! is_array($general['internal_server_error'])) {
            $general['internal_server_error'] = [$general['internal_server_error']];
        }

        return new Collection($general);
    }

    /**
     * Assume sensible defaults for a number of options.
     */
    protected function getDefaultConfig(): array
    {
        return [
            'sitename' => 'Default Bolt site',
            'records_per_page' => 10,
            'records_on_dashboard' => 5,
            'theme' => 'base-2020',
            'listing_template' => 'listing.html.twig',
            'listing_records' => '5',
            'listing_sort' => 'datepublish DESC',
            'enforce_ssl' => false,
            'thumbnails' => [
                'default_thumbnail' => [160, 120],
                'default_image' => [1000, 750],
                'quality' => 75,
                'cropping' => 'crop',
                'notfound_image' => '/assets/images/placeholder.png',
                'error_image' => 'bolt_assets://img/default_error.png',
                'only_aliases' => false,
            ],
            'accept_file_types' => explode(',', 'twig,html,js,css,scss,gif,jpg,jpeg,png,ico,zip,tgz,txt,md,doc,docx,pdf,epub,xls,xlsx,csv,ppt,pptx,mp3,ogg,wav,m4a,mp4,m4v,ogv,wmv,avi,webm,svg,avif,webp'),
            'accept_media_types' => explode(',', 'gif,jpg,jpeg,png,svg,pdf,mp3,tiff,avif,webp'),
            'accept_upload_size' => '8M',
            'upload_location' => '{contenttype}/{year}/{month}/',
            'maintenance_mode' => false,
            'headers' => [
                'allow_floc' => false,
                'powered_by' => true,
            ],
            'htmlcleaner' => [
                'allowed_tags' => explode(',', 'div,span,p,br,hr,s,u,strong,em,i,b,li,ul,ol,mark,blockquote,pre,code,tt,h1,h2,h3,h4,h5,h6,dd,dl,dh,table,tbody,thead,tfoot,th,td,tr,a,img,address,abbr,iframe'),
                'allowed_attributes' => explode(',', 'id,class,style,name,value,href,Bolt,alt,title,width,height,frameborder,allowfullscreen,scrolling'),
                'allowed_frame_targets' => explode(',', '_blank,_self,_parent,_top'),
            ],
            'notfound' => 'helpers/page_404.html.twig',
            'maintenance' => 'helpers/page_503.html.twig',
            'forbidden' => 'helpers/page_403.html.twig',
            'internal_server_error' => 'helpers/page_500.html.twig',
            'localization' => [
                'fallback_when_missing' => true,
                'remove_default_locale_on_canonical' => true,
            ],
            'omit_backgrounds' => false,
            'omit_meta_generator_tag' => false,
            'user_avatar' => [
                'upload_path' => 'avatars',
                'extensions_allowed' => ['png', 'jpeg', 'jpg', 'gif'],
                'default_avatar' => '',
            ],
        ];
    }
}
