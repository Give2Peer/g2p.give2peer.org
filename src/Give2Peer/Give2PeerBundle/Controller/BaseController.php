<?php

namespace Give2Peer\Give2PeerBundle\Controller;

use Doctrine\ORM\EntityManager;
use Give2Peer\Give2PeerBundle\Entity\ItemRepository;
use Give2Peer\Give2PeerBundle\Entity\TagRepository;
use Give2Peer\Give2PeerBundle\Entity\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Utilities, mostly sugar for the eyes and auto completion.
 */
abstract class BaseController extends Controller
{
    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->get('doctrine.orm.entity_manager');
    }

    /**
     * @return UserManager
     */
    protected function getUserManager()
    {
        return $this->get('fos_user.user_manager');
    }

    /**
     * @deprecated
     * @return SecurityContext
     */
    protected function getSecurityContext()
    {
        return $this->get('security.context');
    }

    /**
     * @return ItemRepository
     */
    protected function getItemRepository()
    {
        return $this->getEntityManager()->getRepository('Give2PeerBundle:Item');
    }

    /**
     * @return TagRepository
     */
    protected function getTagRepository()
    {
        return $this->getEntityManager()->getRepository('Give2PeerBundle:Tag');
    }
}