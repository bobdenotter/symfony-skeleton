<?php

declare(strict_types=1);

namespace Bolt\Controller\Backend;

use Bolt\Common\Json;
use Bolt\Controller\CsrfTrait;
use Bolt\Controller\TwigAwareController;
use Bolt\Form\UserFormType;
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @Security("has_role('ROLE_ADMIN')")
 */
class ProfileController extends TwigAwareController implements BackendZone
{
    use CsrfTrait;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var ObjectManager
     */
    private $em;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        ObjectManager $em,
        UserPasswordEncoderInterface $passwordEncoder,
        CsrfTokenManagerInterface $csrfTokenManager
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->em = $em;
        $this->passwordEncoder = $passwordEncoder;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * @Route("/profile-edit", methods={"GET"}, name="bolt_profile_edit")
     */
    public function edit(Request $request): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(UserFormType::class, $user, [
            'action' => $this->urlGenerator->generate('bolt_profile_save'),
            'method' => Request::METHOD_PUT,
        ]);

        return $this->renderTemplate('@bolt/users/edit.html.twig', [
            'display_name' => $user->getDisplayName(),
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/profile-edit", methods={"PUT"}, name="bolt_profile_save")
     */
    public function save(Request $request): Response
    {
        $user = $this->getUser();
        $locale = Json::findScalar($request->get('locale'));
        $request->getSession()->set('_locale', $locale);

        $form = $this->createForm(UserFormType::class, $user, [
            'action' => $this->urlGenerator->generate('bolt_profile_save'),
            'method' => Request::METHOD_PUT,
        ]);

        $form->handleRequest($request);
        if ($form->isValid()) {
            if ($form->getData()->get('plainPassword') !== null) {
                $user->setPassword($this->passwordEncoder->encodePassword(
                    $user,
                    $form->getData()->get('plainPassword')
                ));
            }
            $this->em->flush();

            $url = $this->urlGenerator->generate('bolt_profile_edit');

            return new RedirectResponse($url);
        }

        return $this->renderTemplate('@bolt/users/edit.html.twig', [
            'display_name' => $user->getDisplayName(),
            'user' => $user,
            'form' => $form,
        ]);
    }
}
