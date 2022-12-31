<?php

namespace calips\cfaccess\models;

use Craft;
use craft\base\Model;
use craft\helpers\App;

/**
 * Cloudflare Access settings
 */
class Settings extends Model
{
    public bool $enable = false;
    public bool $enforce = false;
    public ?string $issuer = null;
    public ?string $aud = null;

    public function defineRules(): array
    {
        return [
            [['enable', 'enforce'], 'boolean'],
            [['issuer', 'aud'], 'required', 'when' => function(self $model) { return $model->enable; }],
            [['issuer', 'aud'], 'string'],
        ];
    }

    /**
     * @return string|null
     */
    public function getIssuer(): ?string
    {
        return App::parseEnv($this->issuer);
    }

    /**
     * @return string|null
     */
    public function getAud(): ?string
    {
        return App::parseEnv($this->aud);
    }

    /**
     * @return bool
     */
    public function isEnable(): bool
    {
        return App::parseBooleanEnv($this->enable);
    }

    /**
     * @return bool
     */
    public function isEnforce(): bool
    {
        return App::parseBooleanEnv($this->enforce);
    }
}
