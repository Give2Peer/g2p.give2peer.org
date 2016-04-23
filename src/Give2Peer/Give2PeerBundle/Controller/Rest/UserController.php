<?php

namespace Give2Peer\Give2PeerBundle\Controller\Rest;

use Give2Peer\Give2PeerBundle\Controller\BaseController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Give2Peer\Give2PeerBundle\Controller\ErrorCode as Error;
use Give2Peer\Give2PeerBundle\Entity\Item;
use Give2Peer\Give2PeerBundle\Entity\User;
use Give2Peer\Give2PeerBundle\Response\ErrorJsonResponse;
use Give2Peer\Give2PeerBundle\Response\ExceededQuotaJsonResponse;
use Symfony\Component\Yaml\Yaml;

/**
 * Routes are configured in YAML, in `Resources/config/routing.yml`.
 * ApiDoc's documentation can be found at :
 * https://github.com/nelmio/NelmioApiDocBundle/blob/master/Resources/doc/index.md
 * 
 * 
 */
class UserController extends BaseController
{

    /**
     *
     * todo:
     * <adjective>_<color>_<animal>
     *
     * @return String
     */
    public function generateUsername()
    {
        $dir = "@Give2PeerBundle/Resources/config/";
        $path = $this->get('kernel')->locateResource($dir . 'game.yml');
        $beings = Yaml::parse(file_get_contents($path))['beings'];
        $colors = Yaml::parse(file_get_contents($path))['colors'];
        $adjectives = Yaml::parse(file_get_contents($path))['adjectives'];

        $a = $adjectives[array_rand($adjectives)];
        $b = $beings[array_rand($beings)];
        $c = $colors[array_rand($colors)];

        return "${a}_${c}_${b}";
    }

//        $words = [];
//        foreach($animals as $a) {
//            $b = str_replace(['-',','], ' ', str_replace("'", '', trim($a)));
//            foreach(explode(' ', $b) as $w) {
//                if (strlen($w)) {
//                    $words[] = strtoupper($w{0}) . substr($w,1);
//                }
//            }
//        }
//
//        $words = array_unique($words);
//        sort($words);
//
//        print("------");
//        print(count($animals)."\n");
//        print(count($words)."\n");
//
//        $s = '';
//        foreach($words as $w) {
//            $s .= "- $w\n";
//        }
////        print($s);
//        $f = fopen(__DIR__.'_colors.yml', 'w+');
//        fprintf($f, $s);
//        fclose($f);

//        return $animal;
//    }

    /**
     * Change the authenticated user's password.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function passwordChangeAction(Request $request)
    {
        $password = $request->get('password');
        if (null == $password) {
            return new JsonResponse(["error"=>"No password provided."], 400);
        }

        /** @var User $user */
        $user = $this->getUser();
        
        if (null == $user) {
            return new JsonResponse(["error"=>"No user."], 400);
        }
        
        $user->setPlainPassword($password);

        // This canonicalizes, encodes, persists and flushes
        $um = $this->getUserManager();
        $um->updateUser($user);

        // Send the user as response
        return new JsonResponse(['user'=>$user]);
    }

    /**
     * Basic boring registration. (well... not really)
     * 
     * If you don't provide a password, we'll generate one for you and give it
     * back to you in the response
     * 
     * If you don't provide a username, we'll generate one for you and give it
     * back to you in the response.
     *
     * @ApiDoc(
     *   parameters = {
     *     { "name"="username", "dataType"="string", "required"=false },
     *     { "name"="password", "dataType"="string", "required"=false },
     *     { "name"="email",    "dataType"="string", "required"=true  },
     *   }
     * )
     * @param Request $request
     * @return JsonResponse
     */
    public function registerAction(Request $request)
    {
        // Recover the user data
        $username = $request->get('username');
        $password = $request->get('password');
        $email    = $request->get('email');
        $clientIp = $request->getClientIp();

        // If you don't provide a username, we'll generate one for you
        // and give it back to you in the response.
        $username_generated = null;
        if (null == $username) {
            $username_generated = $this->generateUsername();
            $username = $username_generated;
        }
        
        // If you don't provide a password, we'll generate one for you
        // and give it back to you in the response.
        $password_generated = null;
        if (null == $password) {
            $password_generated = "I swear I'm here to help."; // fixme, you lazy fsck
            $password = $password_generated;
        }
        
        // If you don't provide an email, we'll generate a dummy one
        $email_generated = null;
        if (null == $email) {
            $email_generated = "$username@dummies.give2peer.org";
            $email = $email_generated;
        }

        $um = $this->getUserManager();

        // Rebuke if username is taken
        $user = $um->findUserByUsername($username);
        if (null != $user) {
            return new ErrorJsonResponse(
                "Username already taken.", Error::UNAVAILABLE_USERNAME
            );
        }

        // Rebuke if email is taken
        $user = $um->findUserByEmail($email);
        if (null != $user) {
            return new ErrorJsonResponse(
                "Email already taken.", Error::UNAVAILABLE_EMAIL
            );
        }

        // Rebuke if too many Users created in 2 days from this IP
        // See http://php.net/manual/fr/dateinterval.construct.php
        $allowed = 42;
        $duration = new \DateInterval("P2D");
        $since = (new \DateTime())->sub($duration);
        $count = $um->countUsersCreatedBy($clientIp, $since);
        if ($count > $allowed) {
            return new ExceededQuotaJsonResponse("Too many registrations.");
        }

        // Create a new User
        /** @var User $user */
        $user = $um->createUser();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setPlainPassword($password);
        $user->setCreatedBy($clientIp);
        $user->setEnabled(true);

        // This canonicalizes, encodes, persists and flushes
        $um->updateUser($user);

        // Send the user in the response
        $response = ['user' => $user];
        // Along with the password if one was generated
        if (null != $password_generated) {
            $response['password'] = $password_generated;
        }
        return new JsonResponse($response);
    }


}