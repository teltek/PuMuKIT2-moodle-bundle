<?php

namespace Pumukit\MoodleBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\Up2u\WebTVBundle\Controller\SearchController;

/**
 * @Route("/pumoodle")
 */
class BasicSearchController extends SearchController
{
    /**
     * @Route("/searchmultimediaobjects/{tagCod}/{useTagAsGeneral}", defaults={"tagCod": null, "useTagAsGeneral": false})
     * @ParamConverter("blockedTag", class="PumukitSchemaBundle:Tag", options={"mapping": {"tagCod": "cod"}})
     * @Template("PumukitMoodleBundle:Search:index.html.twig")
     */
    public function searchAction(Request $request, Tag $blockedTag = null, $useTagAsGeneral = false)
    {
        return parent::multimediaObjectsAction($request, $blockedTag, $useTagAsGeneral);
    }

    protected function createMultimediaObjectQueryBuilder()
    {
        $repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $request = $this->get('request_stack')->getMasterRequest();

        if ('/pumoodle/searchmultimediaobjects' == $request->getPathInfo()) {
            $dm = $this->get('doctrine_mongodb.odm.document_manager');
            $dm->getFilterCollection()->disable('frontend');
            $queryBuilder = $repo->createQueryBuilder();
            $queryBuilder->field('status')->equals(0);
            $queryBuilder->field('properties.redirect')->equals(false);
        } else {
            $queryBuilder = $repo->createStandardQueryBuilder();
        }

        return $queryBuilder;
    }    
}
