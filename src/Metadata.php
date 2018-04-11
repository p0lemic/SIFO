<?php

namespace Sifo;

use Sifo\Http\Domains;
use Sifo\Http\Router;
use Sifo\Http\Urls;

/**
 * Manage metadata. Example of use:
 *
 * index.ctrl.php:
 * Metadata::setKey( 'test' );
 * Metadata::setValues( 'name', 'Test name' );
 * Metadata::setValues( 'section', 'Test section' );
 *
 * head.ctrl.php:
 * $this->assign( 'metadata', Metadata::get() );
 *
 * metadata_es_ES.config.php:
 * $config['test'] = array(
 *    'title' => "%name% - %section%. YourBrandName",
 *    'description' => "Description of %name% - %section%",
 *    'keywords' => "%name%,%section%"
 * );
 *
 * FINALLY, THE RESULT IS:
 *
 * $config['test'] = array(
 *    'title' => "Test name - Test section. YourBrandName",
 *    'description' => "Description of Test name - Test section",
 *    'keywords' => "Test name,Test section"
 * );
 *
 */
class Metadata
{
    /**
     * Store metadata key. It's not mandatory define a key. If you don't set a key the metadata key will be the URL path.
     *
     * @param string $key This key should be defined in the metadata_lang.config.php
     */
    public static function setKey($key): void
    {
        self::set(null, $key, true);
    }

    /**
     * Store values to do a replacement in the metadata definition. If an array is passed
     * in the values the var_name is ignored. Keys are used as var_names.
     *
     * @param string $var_name Var name defined in the metadata config.
     * @param string|array $value Value or values to replace in the metadata config (as string or key=>value).
     */
    public static function setValues($var_name, $value): void
    {
        if (\is_array($value)) {
            foreach ($value as $key => $val) {
                self::set("%$key%", $val);
            }
        } else {
            self::set("%$var_name%", $value);
        }
    }

    /**
     * Get metadata. It uses the metadata key or path to return the metadata.
     *
     * @return array
     * @throws Exception\ConfigurationException
     */
    public static function get(): array
    {
        $metadata_info = self::_getMetadataInformation();
        $metadata_raw = Config::getInstance()->getConfig('lang/metadata_' . Domains::getInstance()->getLanguage());
        $metadata = $metadata_raw['default'];

        if (isset($metadata_info['metadata_key'])) {
            $metadata = $metadata_raw[$metadata_info['metadata_key']];
        } else {
            $reversal_path = Router::getReversalRoute(Urls::getInstance(Bootstrap::$instance)->getPath());
            if ($reversal_path && isset($metadata_raw[$reversal_path])) {
                $metadata = $metadata_raw[$reversal_path];
            }
        }

        return self::_replaceVars($metadata, $metadata_info);
    }

    /**
     * Replace Metadata vars in the metadata defined in metadata config.
     *
     * @param array $metadata Metadata get it of metadat config.
     * @param array $metadata_info Metadata info with the vars to do the replacement.
     *
     * @return array
     */
    private static function _replaceVars($metadata, $metadata_info): array
    {
        if (isset($metadata_info['vars']) && is_array($metadata)) {
            foreach ($metadata as $name => $value) {
                $metadata[$name] = strtr($metadata[$name], $metadata_info['vars']);
            }
        }

        return $metadata;
    }

    /**
     * Store metadata information in registry.
     *
     * @param string $key Variable name.
     * @param string $value Variable value.
     * @param boolean $is_metadata_key If it's the metadata key this value is true, others false.
     */
    public static function set($key, $value, $is_metadata_key = false): void
    {
        if (Registry::keyExists('metadata_information')) {
            $metadata_information = Registry::get('metadata_information');
        }

        if ($is_metadata_key) {
            $metadata_information['metadata_key'] = $value;
        } else {
            $metadata_information['vars'][$key] = $value;
        }

        Registry::set('metadata_information', $metadata_information);
    }

    /**
     * Get the metadata information.
     *
     * @return array
     */
    private static function _getMetadataInformation(): array
    {
        $metadata_information = Registry::getInstance()->get('metadata_information');

        if ($metadata_information) {
            return $metadata_information;
        }

        return [];
    }
}
