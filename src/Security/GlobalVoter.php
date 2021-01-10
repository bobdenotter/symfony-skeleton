<?php

declare(strict_types=1);

namespace Bolt\Security;

use Bolt\Configuration\Config;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Tightenco\Collect\Support\Collection;

class GlobalVoter extends Voter
{
    private $security;
    private $globalPermissions;
    private $supportedAttributes;

    public function __construct(Security $security, Config $config)
    {
        $this->security = $security;
        $this->globalPermissions = $config->get('permissions/global');
        if ($this->globalPermissions instanceof Collection) {
            // TODO should we also validate that the values are all simple arrays?
            $globalPermissionNames = array_keys($this->globalPermissions->all());
            foreach ($globalPermissionNames as $attribute) {
                $this->supportedAttributes[] = $attribute;
            }
        } else {
            throw new \DomainException('No global permissions config found');
        }
    }

    protected function supports(string $attribute, $subject)
    {
        return in_array($attribute, $this->supportedAttributes, true);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token)
    {

        $user = $token->getUser();

        if (! $user instanceof UserInterface) {
            // the user must be logged in; if not, deny access
            return false;
        }

        if ($attribute === 'user:status') {
            // users with 'user:edit' also have 'user:status' permission
            if ($this->voteOnAttribute('user:edit', $subject, $token)) {
                return true;
            }
        }

        if (! isset($this->globalPermissions[$attribute])) {
            throw new \DomainException("Global permission '${attribute}' not defined, check your security and permissions configuration.");
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
