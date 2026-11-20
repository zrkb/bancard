<?php

declare(strict_types=1);

namespace Bancard\Operations;

use Bancard\Response\PreauthorizationConfirmResponse;
use Bancard\Util\Token;

/**
 * @extends Operation<PreauthorizationConfirmResponse>
 */
class PreauthorizationConfirm extends Operation
{
    protected string $endpoint = '/vpos/api/0.3/preauthorizations/confirm';

    /** @var class-string<PreauthorizationConfirmResponse> */
    protected string $responseClass = PreauthorizationConfirmResponse::class;

    /**
     * Generate the operation token.
     *
     * Token: md5(private_key + shop_process_id + "pre-authorization-confirm")
     *
     * @return string MD5 hash
     */
    public function token(): string
    {
        return Token::make(
            $this->client->privateKey,
            (string) $this->payload('shop_process_id'),
            'pre-authorization-confirm',
        );
    }

    /**
     * @return list<string>
     */
    protected function rules(): array
    {
        return ['shop_process_id'];
    }

    /**
     * Get integer fields and their maximum digits, per the vPOS spec.
     *
     * @return array<string, int>
     */
    protected function integerFields(): array
    {
        return [
            'shop_process_id' => 15,
        ];
    }
}
