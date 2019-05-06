<?php

namespace Pumukit\MoodleBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Pumukit\SchemaBundle\Services\UserService;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\Group;

/**
 * @Route("/pumoodle")
 */
class SSOController extends Controller
{
    const LDAP_ID_KEY = 'uid';

    /**
     * Parametes:
     *   - email o usename
     *   - hash.
     *
     * @Route("/sso")
     */
    public function ssoAction(Request $request)
    {
        //TODO Disable by default
        if (!$this->container->hasParameter('pumukit.naked_backoffice_domain')) {
            return $this->genError('The domain "pumukit.naked_backoffice_domain" is not configured.');
        }

        $repo = $this
            ->get('doctrine_mongodb.odm.document_manager')
            ->getRepository('PumukitSchemaBundle:User');

        if ($request->get('email')) {
            $type = 'email';
            $value = $request->get('email');
        } elseif ($request->get('username')) {
            $type = 'username';
            $value = $request->get('username');
        } else {
            return $this->genError('Not email or username parameter.');
        }

        $password = $this->container->getParameter('pumukit_moodle.password');
        $domain = $this->container->getParameter('pumukit.naked_backoffice_domain');

        //Check domain
        if ($domain != $request->getHost()) {
            return $this->genError('Invalid Domain!');
        }

        /*
           //Check referer //TODO
           var_dump($request->headers->get('referer'));exit;
         */

        //Check hash
        if ($request->get('hash') != $this->getHash($value, $password, $domain)) {
            return $this->genError('The hash is not valid.');
        }

        //Only HTTPs
        if (!$request->isSecure()) {
            return $this->genError('Only HTTPS connections are allowed.');
        }

        //Find User
        try {
            $user = $repo->findOneBy(array($type => $value));
            if (!$user) {
                $user = $this->createUser(array($type => $value));
            } else {
                //Promote User from Viewer to Auto Publisher
                $this->promoteUser($user);
            }
        } catch (\Exception $e) {
            return $this->genError($e->getMessage());
        }

        /*
           //Only PERSONAL_SCOPE //TODO
           if(!$user->getPermissionProfile() || $user->getPermissionProfile()->getScope() != PermissionProfile::SCOPE_PERSONAL) {
           return new Response('Only valid for users with personal scope');
           }
         */

        $this->login($user, $request);

        return new RedirectResponse('/admin/series');
    }

    private function getHash($email, $password, $domain)
    {
        $date = date('d/m/Y');

        return md5($email.$password.$date.$domain);
    }

    private function login($user, Request $request)
    {
        $token = new UsernamePasswordToken($user, $user->getPassword(), 'public', $user->getRoles());
        $this->get('security.token_storage')->setToken($token);
        $event = new InteractiveLoginEvent($request, $token);
        $this->get('event_dispatcher')->dispatch('security.interactive_login', $event);
    }

    private function createUser($info)
    {
        $ldapService = $this->get('pumukit_ldap.ldap');
        $permissionProfileService = $this->get('pumukitschema.permissionprofile');
        $userService = $this->container->get('pumukitschema.user');
        $personService = $this->container->get('pumukitschema.person');

        if (array_key_exists('email', $info)) {
            $key = 'mail';
            $value = $info['email'];
        } elseif (array_key_exists('username', $info)) {
            $key = self::LDAP_ID_KEY;
            $value = $info['username'];
        } else {
            throw new \RuntimeException('No email or username given');
        }

        $info = $ldapService->getInfoFrom($key, $value);

        if (!isset($info) || !$info) {
            throw new \RuntimeException('User "'.$value.'" not found in LDAP on creating (using LDAP '.$key.' attribute).');
        }
        //TODO Move to a service
        if (!isset($info['edupersonprimaryaffiliation'][0]) ||
            !in_array($info['edupersonprimaryaffiliation'][0], array('PAS', 'PDI'))) {
            throw new \RuntimeException('User invalid.');
        }

        //TODO create createDefaultUser in UserService.
        //$this->userService->createDefaultUser($user);
        $user = new User();
        $user->setUsername($info[self::LDAP_ID_KEY][0]);
        $user->setEmail($info['mail'][0]);

        $permissionProfile = $permissionProfileService->getByName('Auto Publisher');
        $user->setPermissionProfile($permissionProfile);
        $user->setOrigin('moodle');
        $user->setEnabled(true);

        $userService->create($user);
        $group = $this->getGroup($info['edupersonprimaryaffiliation'][0]);
        $userService->addGroup($group, $user, true, false);
        $personService->referencePersonIntoUser($user);

        return $user;
    }

    private function getGroup($key)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $repo = $dm->getRepository('PumukitSchemaBundle:Group');
        $groupService = $this->get('pumukitschema.group');

        $cleanKey = preg_replace('/\W/', '', $key);

        $group = $repo->findOneByKey($cleanKey);
        if ($group) {
            return $group;
        }

        $group = new Group();
        $group->setKey($cleanKey);
        $group->setName($key);
        $group->setOrigin('cas');
        $groupService->create($group);

        return $group;
    }

    //Promote User from Viewer to Auto Publisher
    private function promoteUser(User $user)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $permissionProfileService = $this->get('pumukitschema.permissionprofile');
        $userService = $this->get('pumukitschema.user');

        $permissionProfileViewer = $permissionProfileService->getByName('Viewer');
        $permissionProfileAutoPub = $permissionProfileService->getByName('Auto Publisher');

        if ($permissionProfileViewer == $user->getPermissionProfile() && $this->has('pumukit_ldap.ldap')) {
            $ldapService = $this->get('pumukit_ldap.ldap');
            $info = $ldapService->getInfoFromEmail($user->getEmail());

            if (!$info) {
                throw new \RuntimeException('User "'.$user->getEmail().'" not found in LDAP on promoting (using LDAP mail attribute).');
            }
            //TODO Move to a service
            if (!isset($info['edupersonprimaryaffiliation'][0]) ||
                !in_array($info['edupersonprimaryaffiliation'][0], array('PAS', 'PDI'))) {
                throw new \RuntimeException('User invalid.');
            }

            $user->setPermissionProfile($permissionProfileAutoPub);
            $userService->update($user, true, false);
        }
    }

    private function genError($message = 'Not Found', $status = 404)
    {
        return new Response(
            $this->renderView('PumukitMoodleBundle:SSO:error.html.twig', array('message' => $message)),
            $status
        );
    }
}
