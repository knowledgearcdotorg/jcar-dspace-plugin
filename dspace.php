<?php
/**
 * @package     JCar.Plugin
 *
 * @copyright   Copyright (C) 2015 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

/**
 * Retrieves information from a REST API-enabled DSpace archive.
 */
class PlgJCarDSpace extends JPlugin
{
    public function __construct(&$subject, $config)
    {
        $this->autoloadLanguage = true;
        parent::__construct($subject, $config);

        JLog::addLogger(array());
    }

    /**
     * Gets an item from a REST API-enabled DSpace archive.
     *
     * @param  int    $id  The id of an item to retrieve from the DSpace archive.
     *
     * @param  mixed  An item from the REST API-enabled DSpace archive, or null if nothing could be found.
     */
    public function onJCarItemAfterRetrieve($id)
    {
        $parts = explode(":", $id, 2);

        if (count($parts) == 2) {
            $id = JArrayHelper::getValue($parts, 1);
        }

        return $this->getItem($id);
    }

    /**
     * Gets an item from the REST API-enabled DSpace archive.
     *
     * @param  int    $id  The id of an item to retrieve from the DSpace archive.
     *
     * @param  mixed  An item from the REST API-enabled DSpace archive, or null if nothing could be found.
     */
    private function getItem($id)
    {
        $url = $this->params->get('rest_url').'/items/'.$id.'.json';

        JLog::add($url, JLog::DEBUG, 'jcardspace');

        $http = JHttpFactory::getHttp();

        $response = $http->get($url);

        if ($response->code === 200) {
            $data = json_decode($response->body);

            $array = array();
            foreach ($data->metadata as $metadata) {
                $key = $metadata->schema.'.'.$metadata->element;

                if (isset($metadata->qualifier)) {
                    $key .= '.'.$metadata->qualifier;
                }

                if (!JArrayHelper::getValue($array, $key)) {
                    $array[$key] = array();
                }

                $array[$key][] = $metadata->value;
            }

            $data->metadata = $array;

            $data->bundles = $this->getBundles($id);

            return $data;
        } else {
            JLog::add($response->body, JLog::ERROR, 'jcardspace');
            throw new Exception(JText::_('PLG_JCAR_DSPACE_ERROR_'.$response->code), $response->code);
        }
    }

    /**
     * Gets bundle information for the specified item from the REST API-enabled DSpace archive.
     *
     * @param  int    $item  The item id of the bundles to retrieve from the DSpace archive.
     *
     * @param  array  Bundle information for the specified item from the REST API-enabled DSpace archive.
     */
    private function getBundles($item)
    {
        $url = $this->params->get('rest_url').'/items/'.$item.'/bundles.json';
        JLog::add($url, JLog::DEBUG, 'jcardspace');

        $http = JHttpFactory::getHttp();
        $response = $http->get($url);

        if ($response->code === 200) {
            $data = json_decode($response->body);

            return $data;
        } else {
            JLog::add($response->body, \JLog::ERROR, 'jcardspace');
            throw new Exception(JText::_('PLG_JCAR_DSPACE_ERROR_'.$response->code), $response->code);
        }
    }
}