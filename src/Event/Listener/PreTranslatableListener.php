<?php

declare(strict_types=1);

namespace Bolt\Event\Listener;

use Bolt\Entity\Translatable;
use Doctrine\Common\EventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Gedmo\Translatable\TranslatableListener;

class PreTranslatableListener extends TranslatableListener
{
    public function postLoad(EventArgs $args): void
    {
        $previousSkipOnLoad = $this->isSkipOnLoad();

        if ($args instanceof LifecycleEventArgs) {
            $entity = $args->getObject();
            if ($entity instanceof Translatable) {
                $this->setLocale($entity);
                if ($entity->getLocale() !== null) {
                    // Entity has changed it's locale,
                    // so to get proper translations with $em->refresh($entity)
                    // skipOnLoad flag from TranslatableListener needs to be temporarily removed.
                    // Otherwise, postLoad event would be internally skipped.
                    $this->setSkipOnLoad(false);
                }
            }
        }

        parent::postLoad($args);
        $this->setSkipOnLoad($previousSkipOnLoad);
    }

    private function setLocale(Translatable $entity): void
    {
        if ($entity->getLocale() !== null) {
            // locale has been set explicitly, no need to change it
            return;
        }

        // set locale injected from request
        $entity->setLocale($this->locale);
    }
}
