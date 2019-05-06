<?php

namespace Pumukit\MoodleBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class FilterListener
{
    private $dm;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $req = $event->getRequest();
        $routeParams = $req->attributes->get('_route_params');

        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()
            && (false !== strpos($req->attributes->get('_controller'), 'MoodleBundle'))
            && (!isset($routeParams['filter']) || $routeParams['filter'])) {
            $filter = $this->dm->getFilterCollection()->enable('frontend');
            $filter->setParameter('pub_channel_tag', array('$in' => array('PUCHWEBTV', 'PUCHMOODLE')));
            $filter->setParameter('status', MultimediaObject::STATUS_PUBLISHED);
            $filter->setParameter('display_track_tag', 'display');
        }
    }
}
