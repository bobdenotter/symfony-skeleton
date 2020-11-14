<?php

declare(strict_types=1);

namespace Bolt\Security;

use Bolt\Configuration\Config;
use Bolt\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class GlobalVoter extends Voter
{
    private $security;
    private $globalPermissions;
    private $supportedAttributes;

    public function __construct(Security $security, Config $config)
    {
        $this->security = $security;
        $this->globalPermissions = $config->get('permissions/global');
        if (is_array($this->globalPermissions) || $this->globalPermissions instanceof \ArrayAccess) {
            // TODO should we also validate that the values are all simple arrays?
            foreach ($this->globalPermissions as $key => $value) {
                $this->supportedAttributes[] = $key;
            }
        } else {
            throw new \DomainException("No global permissions config found");
        }
    }

    protected function supports(string $attribute, $subject)
    {
        return in_array($attribute, $this->supportedAttributes, true);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token)
    {
//        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
//            return true;
//        }

        $user = $token->getUser();

        if (! $user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        if (!isset($this->globalPermissions[$attribute])) {
            throw new \DomainException("Global permission '$attribute' not defined, check your security and permissions configuration.");
        }

        $rolesWithPermission = $this->globalPermissions[$attribute];
        foreach ($rolesWithPermission as $role) {
            if ($this->security->isGranted($role)) {
                return true;
            }
        }
        return false;
    }
}
