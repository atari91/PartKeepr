<?php
namespace PartKeepr\CategoryBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use PartKeepr\CategoryBundle\Entity\AbstractCategory;
use PartKeepr\CategoryBundle\Exception\OnlySingleRootNodeAllowedException;
use PartKeepr\CategoryBundle\Exception\RootNodeNotFoundException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAware;

class RootCategoryListener extends ContainerAware
{
    /**
     * @var string
     */
    private $service;

    public function __construct(Container $container, $service)
    {
        $this->setContainer($container);
        $this->service = $service;
    }

    /**
     * Checks that only one root category exists
     *
     * @param OnFlushEventArgs $eventArgs The event arguments as given by Doctrine
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $entityManager = $eventArgs->getEntityManager();
        $uow = $entityManager->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $updated) {

            if (is_a($updated, $this->container->get($this->service)->getEntityClass())) {
                $this->checkForRoot($updated);
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $updated) {
            if (is_a($updated, $this->container->get($this->service)->getEntityClass())) {
                $this->checkForRoot($updated);
            }
        }
    }

    protected function checkForRoot(AbstractCategory $category)
    {
        if ($category->getParent() === null) {
            try {
                $rootNode = $this->container->get($this->service)->getRootNode();

                if ($rootNode->getId() != $category->getId()) {
                    throw new OnlySingleRootNodeAllowedException();
                }
            } catch (RootNodeNotFoundException $e) {

            }
        }
    }
}
