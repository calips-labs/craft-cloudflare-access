<?php

namespace calips\cfaccess\controllers;

use calips\cfaccess\CloudflareAccess;
use Craft;
use craft\web\Controller;
use craft\web\View;
use yii\web\Response;

/**
 * Cf Inspect controller
 */
class CfInspectController extends Controller
{
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;

    public function actionTestAccess(): Response
    {
        $plugin = CloudflareAccess::getInstance();
        $jwt = $plugin->cloudflareValidation->getJwtFromHeaders();

        $validationResult = null;

        if ($jwt != null) {
            $validationResult = $plugin->cloudflareValidation->verifyJwt($jwt);
        }

        return $this->renderTemplate(
            'cloudflare-access/test-access.twig',
            [
                'jwt' => $jwt,
                'result' => $validationResult,
                'issuer' => $plugin->settings->getIssuer(),
                'aud' => $plugin->settings->getAud(),
            ],
            View::TEMPLATE_MODE_CP
        );
    }
}
